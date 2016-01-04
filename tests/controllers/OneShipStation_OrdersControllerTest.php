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
}
