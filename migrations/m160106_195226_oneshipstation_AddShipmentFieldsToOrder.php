<?php
namespace Craft;

/**
* The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
*/
class m160106_195226_oneshipstation_AddShipmentFieldsToOrder extends BaseMigration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp()
    {
        if ($orderSettings = craft()->commerce_orderSettings->getOrderSettingByHandle('order')) {
            if ($shippingInfoField = craft()->fields->getFieldByHandle('shippingInfo')) {
                $fieldLayout = $orderSettings->getFieldLayout();

                //create field
                $fieldRecord = new FieldLayoutFieldRecord();
                $fieldRecord->layoutId  = $fieldLayout->id;
                $fieldRecord->fieldId   = $shippingInfoField->id;
                $fieldRecord->required  = true;
                $fieldRecord->sortOrder = 1;

                //find or create One ShipStation tab
                $shipstationTabName = craft()->plugins->getPlugin('oneShipStation')->name;
                $shipstationTab = array_shift(array_filter($fieldLayout->getTabs(), function($tab) { return $tab->name == 'One ShipStation'; }));

                //no tab found (this is most likely), so create tab and field record
                if (!$shipstationTab) {
                    Craft::log('No One ShipStation tab found, creating one');
                    $tabRecord = new FieldLayoutTabRecord();
                    $tabRecord->layoutId  = $fieldLayout->id;
                    $tabRecord->name      = $shipstationTabName;
                    $tabRecord->sortOrder = count($fieldLayout->getTabs() ?: array()) + 1;

                    //save the tab to get its ID
                    if ($tabRecord->save()) {
                        $fieldRecord = new FieldLayoutFieldRecord();
                        $fieldRecord->layoutId  = $fieldLayout->id;
                        $fieldRecord->tabId     = $tabRecord->id;
                        $fieldRecord->fieldId   = $shippingInfoField->id;
                        $fieldRecord->required  = false;
                        $fieldRecord->sortOrder = 1;

                        //yay! save the field record
                        if ($fieldRecord->save()) {
                            Craft::log('Successfully added shipping info to order settings.');
                            return true;
                        } else {
                            Craft::log('Failed to add shipping fields to Order model: failed to save field record.');
                        }
                    } else {
                        Craft::log('Failed to add shipping fields to Order model: failed to save tab record.');
                    }

                } else {
                    //break if field already exists
                    foreach ($shipstationTab->getFields() as $field) {
                        if ($field->getField()->handle == $shippingInfoField->handle) {
                            Craft::log('Shipping info field already exists on Order. No action taken.');
                            return true;
                        }
                    }

                    //save new field record within the found tab
                    $fieldRecord->tabId = $shipstationTab->id;
                    if ($fieldRecord->save(false)) {
                        Craft::log('Successfully added shipping field to order');
                        return true;
                    } else {
                        Craft::log('Failed to save shipping info field');
                    }
                }

            } else {
                Craft::log('Failed to add shipping fields to Order model: shippingInfo field not found');
            }
        } else {
            Craft::log('Failed to add shipping fields to Order model: Craft Order Settings not found.');
        }
        return false;
    }
}
