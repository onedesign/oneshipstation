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
        switch (craft()->request->getQuery('action')) {
            case 'export':
                return $this->getOrders();
            case 'shipnotify':
                return $this->postShipment();
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
        //TODO
        return true;
    }

    /**
     * Renders a big XML file of all orders in a format described by ShipStation
     */
    protected function getOrders() {
        //TODO
        return true;
    }

    /**
     * Updates order status for a given order, as posted here by ShipStation
     */
    protected function postShipment() {
        //TODO
        return true;
    }
}
