<?php
namespace Craft;

class Oneshipstation_OrdersController extends BaseController
{
    protected $allowAnonymous = true;

    /**
     * ShipStation will hit this action for processing orders, both POSTing and GETting.
     *   ShipStation will send a GET param 'action' of either shipnotify or export.
     *   If this is not found or is any other string, this will throw a 400 exception.
     *
     * @param array $variables, containing key 'fulfillmentService'
     * @throws HttpException for malformed requests
     */
    public function actionProcess(array $variables=[]) {
        if (!$this->authenticate()) {
            throw new HttpException(401);
        }
        try {
            switch (craft()->request->getParam('action')) {
                case 'export':
                    return $this->getOrders();
                case 'shipnotify':
                    return $this->postShipment();
                default:
                    throw new HttpException(400);
            }
        } catch (ErrorException $e) {
            Craft::log($e->getMessage(), LogLevel::Error, true);
            return $this->returnErrorJson($e->getMessage());
        } catch (Exception $e) {
            Craft::log($e->getMessage(), LogLevel::Error, true);
            return $this->returnErrorJson($e->getMessage());
        }
    }

    /**
     * Authenticate the user using HTTP Basic auth. This is NOT using Craft's sessions/authentication.
     *
     * @return bool, true if successfully authenticated or false otherwise
     */
    protected function authenticate() {
        $expectedUsername = craft()->plugins->getPlugin('oneshipstation')->getSettings()->oneshipstation_username;
        $expectedPassword = craft()->plugins->getPlugin('oneshipstation')->getSettings()->oneshipstation_password;
        $username = array_key_exists('PHP_AUTH_USER', $_SERVER) ? $_SERVER['PHP_AUTH_USER'] : null;
        $password = array_key_exists('PHP_AUTH_PW', $_SERVER) ? $_SERVER['PHP_AUTH_PW'] : null;

        return $expectedUsername == $username && $expectedPassword == $password;
    }

    /**
     * Renders a big XML file of all orders in a format described by ShipStation
     * Note: this should probably get orders using Craft Commerce's variable/service if possible
     *
     * @param DateTime $start
     * @param DateTime $end
     *
     * @return Commerce_OrderModel[]|null
     */
    protected function getOrders() {
        $criteria = craft()->elements->getCriteria('Commerce_Order');
        if ($start_date = $this->parseDate('start_date') && $end_date = $this->parseDate('end_date')) {
            $criteria->dateOrdered = array('and', '> '.$start_date, '< '.$end_date);
        }
        $criteria->orderStatusId = true;

        $num_pages = $this->paginateOrders($criteria);

        $parent_xml = new \SimpleXMLElement('<Orders />');
        $parent_xml->addAttribute('pages', $num_pages);

        craft()->oneShipStation_xml->orders($parent_xml, $criteria->find());

        $this->returnXML($parent_xml);
    }

    /**
     * For a Criteria instance of Orders, return the number of total pages and apply a corresponding offset and limit
     *
     * @param ElementCriteriaModel, a REFERENCE to the criteria instance
     * @return Int total number of pages
     */
    protected function paginateOrders(&$criteria) {
        $pageSize = craft()->plugins->getPlugin('OneShipStation')->getSettings()->orders_page_size;
        if (!is_numeric($pageSize) || $pageSize < 1) {
            $pageSize = 25;
        }

        $numPages = ceil($criteria->total() / $pageSize);
        $pageNum = craft()->request->getParam('page');
        if (!is_numeric($pageNum) || $pageNum < 1) {
            $pageNum = 1;
        }

        $criteria->limit = $pageSize;
        $criteria->offset = ($pageNum - 1) * $pageSize;

        return $numPages;
    }

    /**
     * For a given date field, parse and return its date as a string
     *
     * @param String $field_name, the name of the field in GET params
     * @return String|null the formatted date string
     */
    protected function parseDate($field_name) {
        if ($date_raw = craft()->request->getParam($field_name)) {
            $date = strtotime($date_raw);
            if ($date !== false) {
                if ($field_name === 'start_date')
                    return date('Y-m-d H:i:s', $date);
                else
                    return date('Y-m-d H:i:59', $date);
            }
        }
        return null;
    }

    /**
     * Updates order status for a given order, as posted here by ShipStation.
     * The order is found using GET param order_number.
     *
     * See craft/plugins/commerce/controllers/Commerce_OrdersController.php#actionUpdateStatus() for details
     *
     * @throws ErrorException if the order fails to save
     */
    protected function postShipment() {
        $order = $this->orderFromParams();

        $status = craft()->commerce_orderStatuses->getOrderStatusByHandle('shipped');
        if (!$status) {
            throw new ErrorException("Failed to find Commerce OrderStatus 'Shipped'");
        }

        $order->orderStatusId = $status->id;
        $order->message = $this->orderStatusMessageFromShipstationParams();

        if (craft()->commerce_orders->saveOrder($order)) {
            $shippingInformation = $this->getShippingInformationFromParams();
            if (!craft()->oneShipStation_shippingLog->logShippingInformation($order, $shippingInformation)) {
                throw new ErrorException('Logging shipping information failed for order ' . $order->id);
            }

            $this->returnJson(['success' => true]); //TODO return 200 success
        } else {
            throw new ErrorException('Failed to save order with id ' . $order->id);
        }
    }

    /**
     * Craft Commerce stores a message along with all Order Status changes.
     * We'll leverage that to store the carrier, service, and tracking number sent to us from ShipStation.
     *
     * In the future we may prefer this to be rendered in a template, or even stored in another variable.
     *
     * @return String
     */
    protected function orderStatusMessageFromShipstationParams() {
        $message = array();
        foreach ($this->getShippingInformationFromParams() as $field => $value) {
            $message[] = $field . ': ' . $value;
        }
        return implode($message, ', ');
    }

    /**
     * Parse parameters POSTed from ShipStation for fields available to us on the Order's shippingInfo matrix field
     *
     * Note: only fields that exist in the matrix block will be set.
     *       ShipStation posts, in XML, many more fields than these, but for now we disregard.
     *       https://help.shipstation.com/hc/en-us/articles/205928478-ShipStation-Custom-Store-Development-Guide#2ai
     *
     * @return array
     */
    protected function getShippingInformationFromParams() {
        return ['carrier' => craft()->request->getParam('carrier'),
                'service' => craft()->request->getParam('service'),
                'trackingNumber' => craft()->request->getParam('tracking_number')
        ];
    }

    /**
     * Find the order model given the order_number passed to us from ShipStation.
     *
     * Note: the order_number value from ShipStation corresponds to $order->number that we
     *       return to ShipStation as part of the getOrders() method above.
     *
     * @throws HttpException, 404 if not found, 406 if order number is invalid
     * @return Commerce_Order
     */
    protected function orderFromParams() {
        if ($order_number = craft()->request->getParam('order_number')) {
            if ($order = craft()->commerce_orders->getOrderByNumber($order_number)) {
                return $order;
            }
            throw new HttpException(404, "Order with number '{$order_number}' not found");
        }
        throw new HttpException(406, 'Order number must be set');
    }

    /**
     * Responds to the request with XML.
     *
     * See craft/app/controllers/BaseController.php#returnJson() for comparisons
     *
     * @param SimpleXMLElement $xml
     * @return null
     */
    protected function returnXML(\SimpleXMLElement $xml) {
        HeaderHelper::setContentTypeByExtension('xml');
        // Output it into a buffer, in case TasksService wants to close the connection prematurely
        ob_start();
        echo $xml->asXML();

        craft()->end();
    }
}
