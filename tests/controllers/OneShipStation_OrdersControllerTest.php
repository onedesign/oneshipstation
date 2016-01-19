<?php
namespace Craft;

class OneShipStation_OrdersControllerTest extends BaseTest
{
    protected $settings;
    protected $controller;

    public function setup() {
        parent::setup();

        craft()->plugins->loadPlugins();
        $this->settings = craft()->plugins->getPlugin('oneshipstation')->getSettings();
        //TODO determine why this isn't getting bootstrapped as part of BaseTest
        foreach (glob('../commerce/Commerce/Traits/*.php') as $trait) { require_once($trait); }
        foreach (glob('../commerce/Commerce/Helpers/*.php') as $trait) { require_once($trait); }

        $this->settings->oneshipstation_username = "hello";
        $this->settings->oneshipstation_password = "world";

        //TODO determine what these parameters are
        $this->controller = new Oneshipstation_OrdersController(null, null);
    }

    public function testAuthenticationSucceedsWithCorrectParameters() {
        $_SERVER['PHP_AUTH_USER'] = $this->settings->oneshipstation_username;
        $_SERVER['PHP_AUTH_PW'] = $this->settings->oneshipstation_password;

        $authenticate_method = $this->getMethod($this->controller, 'authenticate');
        $authenticated = $authenticate_method->invoke($this->controller);
        $this->assertTrue($authenticated);
    }

    public function testAuthenticationFailsWithNoParameters() {
        unset($_SERVER['PHP_AUTH_USER']);
        unset($_SERVER['PHP_AUTH_PW']);

        $authenticate_method = $this->getMethod($this->controller, 'authenticate');
        $authenticated = $authenticate_method->invoke($this->controller);
        $this->assertFalse($authenticated, 'User was authenticated with no basic auth parameters');
    }

    public function testAuthenticationFailsWithInvalidParameters() {
        $_SERVER['PHP_AUTH_USER'] = 'bogus user';
        $_SERVER['PHP_AUTH_PW'] = 'bogus password';

        $authenticate_method = $this->getMethod($this->controller, 'authenticate');
        $authenticated = $authenticate_method->invoke($this->controller);
        $this->assertFalse($authenticated, 'User was authenticated with invalid basic auth parameters');
    }

    public function testOrderFromParamsThrowsExceptionForEmptyOrderNumber() {
        $this->setExpectedException('\Craft\HttpException');
        $get_order_method = $this->getMethod($this->controller, 'orderFromParams');
        $order = $get_order_method->invoke($this->controller);
    }

    public function testOrderFromParamsThrowsExceptionIfOrderNotFound() {
        $_POST['order_number'] = md5(time());
        $this->setExpectedException('\Craft\HttpException');
        $get_order_method = $this->getMethod($this->controller, 'orderFromParams');
        $order = $get_order_method->invoke($this->controller);
    }

    public function testOrderFromParamsReturnsValidOrder() {
        $unshipped = craft()->commerce_orderStatuses->getOrderStatusByHandle('processing');
        $test_order = Commerce_OrderRecord::model()->findAllByAttributes(['orderStatusId' => $unshipped->id])[0];
        $_POST['order_number'] = $test_order->number;

        $get_order_method = $this->getMethod($this->controller, 'orderFromParams');
        $order = $get_order_method->invoke($this->controller);

        $this->assertNotNull($order);
        $this->assertEquals($order->number, $test_order->number);
    }

    public function testInvalidOrderFromParamsRaisesException() {
        $_POST['order_number'] = md5(time());

        $this->setExpectedException('\Craft\HttpException');
        $get_order_method = $this->getMethod($this->controller, 'orderFromParams');
        $order = $get_order_method->invoke($this->controller);

    }

    public function testMessageIsDefinedFromShipstationParams() {
        $_POST['carrier'] = $carrier = md5(time());
        $_POST['service'] = $service = md5(time());
        $_POST['tracking_number'] = $tracking_number = md5(time());

        $message_method = $this->getMethod($this->controller, 'orderStatusMessageFromShipstationParams');
        $message = $message_method->invoke($this->controller);

        $this->assertNotNull($message);
        foreach ([$carrier, $service, $tracking_number] as $field) {
            $this->assertContains($field, $message);
        }
    }
}
