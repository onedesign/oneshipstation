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

    public function onBeforeInstall() {}

    public function onAfterInstall() {}

    public function onBeforeUninstall() {}

    public function createTables() {}

    public function dropTables() {}

    public function registerSiteRoutes() {
        return array();
    }

}
