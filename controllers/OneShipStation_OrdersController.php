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
        switch (craft()->request->getQuery('action')) {
            case 'export':
                return $this->getOrders();
            case 'shipnotify':
                return $this->postShipment();
            case 'daterange':
                return $this->getOrdersBetween(new DateTime('last month'), new DateTime('now'));
            default:
                throw new HttpException(400);
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
     */
    protected function getOrders() {
        $orders = craft()->elements->getCriteria('Commerce_Order');

        $parent_xml = new \SimpleXMLElement('<Orders />');
        craft()->oneShipStation_xml->orders($parent_xml, $orders);

        $this->returnXML($parent_xml);
    }

    /**
     * Returns a Commerce_OrderModel[] with orders between the $start date and $end date.
     *
     * @param DateTime $start
     * @param DateTime $end
     *
     * @return Commerce_OrderModel[]|null
     */
    protected function getOrdersBetween($start, $end) {
        $criteria = craft()->elements->getCriteria('Commerce_Order');
        $criteria->dateOrdered = array('and', '> '.date_format($start, 'Y-m-d H:i:s'),
                                              '< '.date_format($end, 'Y-m-d H:i:s'));
        return $criteria->find();
    }

    /**
     * Updates order status for a given order, as posted here by ShipStation
     */
    protected function postShipment() {
        //TODO
        return true;
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
