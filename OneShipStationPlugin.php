<?php
namespace Craft;


class OneShipStationPlugin extends BasePlugin {

    public function getName() {
        return Craft::t('One ShipStation');
    }

    public function getVersion() {
        return '0.2.17';
    }

    public function getSchemaVersion() {
        return '0.2';
    }

    public function getDocumentationUrl() {
        return 'https://onedesigncompany.com/craft-cms/plugins/craft-shipstation-plugin';
    }

    public function getDeveloper() {
        return 'One Design Company';
    }

    public function getDeveloperUrl() {
        return 'https://onedesigncompany.com';
    }

    protected function doDepChecks() {
        // require Craft 2.5+
        if (version_compare(craft()->getVersion(), '2.5', '<')) {
            throw new Exception('One ShipStation requires Craft CMS 2.5+ in order to run.');
        }

        // require PHP 5.4+
        if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50400) {
            throw new Exception('One ShipStation requires PHP 5.4+ in order to run.');
        }

        // require Craft Commerce 1.0+
        if (!($commerce = craft()->plugins->getPlugin('commerce')) || version_compare($commerce->getVersion(), '1.0', '<')) {
            throw new Exception('One ShipStation requires Craft Commerce 1.0+.');
        }

        if (!extension_loaded('xml')) {
            throw new Exception('One ShipStation requires the xml extension to be installed.');
        }
    }

    public function onBeforeInstall() {
        $this->doDepChecks();
        return true;
    }

    public function onAfterInstall() {}

    public function onBeforeUninstall() {}

    public function createTables() {}

    public function dropTables() {}

    public function init() {
        $this->doDepChecks();
    }

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

    protected function defineSettings() {
        return array(
            'oneshipstation_username' => array(AttributeType::String),
            'oneshipstation_password' => array(AttributeType::String),
            'orders_page_size'        => array(AttributeType::Number, 'default' => 25),
            'order_id_prefix'         => array(AttributeType::String),
        );
    }

    public function getSettingsHtml() {
        return craft()->templates->render('oneshipstation/settings', array(
            'settings' => $this->getSettings()
        ));
    }
}
