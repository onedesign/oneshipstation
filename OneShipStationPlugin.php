<?php
namespace Craft;


class OneShipStationPlugin extends BasePlugin {

    public function getName() {
        return Craft::t('One ShipStation');
    }

    public function getVersion() {
        return '0.1';
    }

    public function getDeveloper() {
        return 'One Design Company';
    }

    public function getDeveloperUrl() {
        return 'https://onedesigncompany.com';
    }

    public function onBeforeInstall() {
        // require Craft 2.5+
        if (version_compare(craft()->getVersion(), '2.5', '<')) {
            throw new Exception('One ShipStation requires Craft CMS 2.5+ in order to run.');
        }

        // require PHP 5.4+
        if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50400) {
            Craft::log('One ShipStation requires PHP 5.4+ in order to run.', LogLevel::Error);
            return false;
        }

        // require Craft Commerce 1.0+
        if (!($commerce = craft()->plugins->getPlugin('commerce')) || version_compare($commerce->getVersion(), '1.0', '<')) {
            Craft::log('One ShipStation requires Craft Commerce 1.0+.', LogLevel::Error);
            return false;
        }

        return true;
    }

    public function onAfterInstall() {}

    public function onBeforeUninstall() {}

    public function createTables() {}

    public function dropTables() {}

    /*
     * WARNING: Do not register any routes that ShipStation will use here.
     *          ShipStation sends a parameter `action` with every request, which collides with
     *          Craft's action request handling. Therefore, any request from ShipStation MUST
     *          be routed as an action request using the actionTrigger defined in config/general (default: "actions").
     *
     *          `_checkRequestType()` in craft/app/services/HttpRequestService.php determines the request type in this order:
     *
     *          1. the first URL segment matches config's actionTrigger
     *          2. the GET or POST param `action` is set at not null
     *          3. the request is a special path
     *
     *          If any of these are true, Craft handles routing and never checks for plugins' registerSiteRoutes()
     */
    public function registerSiteRoutes() { return []; }

}
