<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m160106_182029_oneshipstation_AddShipmentFields extends BaseMigration
{
    /**
     * Add a Hero field type for use in product pages, as well as the index page.
     * Because this hero matrix block is liekly to exist in many places, we'll put it in the General field group.
     *
     * @return bool
     */
    public function safeUp()
    {
       //first, check to see if the shipping matrix field already exists
       $matrixHandle = 'shippingInfo';
       if ($heroField = craft()->fields->getFieldByHandle($matrixHandle)) {
           Craft::log('Shipping Information matrix field already exists. No action taken.');
           return true;
       }

       //next, make sure the group exists. we can't create a field unless we have a group for it to belong to
        $groupName = craft()->plugins->getPlugin('oneShipStation')->name;
       $group = array_shift(array_filter(craft()->fields->getAllGroups(), function($group) use ($groupName) {
           return $group->name == $groupName;
       }));
       if (is_null($group)) {
           $group = new FieldGroupModel();
           $group->name = $groupName;
           craft()->fields->saveGroup($group);
       }

       //create block
       $block = new MatrixBlockTypeModel();
       $block->handle = 'shipingInfo';
       $block->name   = 'Shipping Info';
       $block->setFields([
           $this->getField('Carrier', 'carrier'),
           $this->getField('Service', 'service'),
           $this->getField('Tracking Number', 'trackingNumber')
       ]);

       //matrix
       $matrix = $this->getField('Shipping Information', $matrixHandle, 'Matrix');
       $matrix->groupId = $group->id;
       $matrix->instructions = 'Shipping information will be provided by ShipStation once orders have been shipped.';
       $matrix->settings = new MatrixSettingsModel($matrix);
       $matrix->settings->setBlockTypes([$block]);
       $matrix->settings->maxBlocks = 1; //only allow one block

       if (craft()->fields->saveField($matrix)) {
           Craft::log('Successfully created Shipping Information Matrix Block field.');
           return true;
       } else {
           Craft::log('Failed to create Shipping Information Matrix Block field.');
           return false;
       }
    }

    protected function getField($name, $handle, $type = 'PlainText') {
        $field = new FieldModel();
        $field->type = $type;
        $field->name = $name;
        $field->handle = $handle;
        return $field;
    }
}
