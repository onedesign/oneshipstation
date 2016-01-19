<?php
namespace Craft;

class OneShipStation_ShippingLogService extends BaseApplicationComponent {

    /**
     * Log new values in the matrix field shippingInfo
     *
     * @param Commerce_OrderModel $order
     * @param array $attributes
     * @return bool success
     */
    public function logShippingInformation($order, $attributes) {
        $infoMatrix = craft()->fields->getFieldByHandle('shippingInfo');
        $blockType  = array_shift(craft()->matrix->getBlockTypesByFieldId($infoMatrix->id));

        if ($infoMatrix && $blockType && $order) {
            $block = new MatrixBlockModel();
            $block->ownerId = $order->id;
            $block->fieldId = $infoMatrix->id;
            $block->typeId = $blockType->id;
            $block->getContent()->setAttributes($attributes);

            return craft()->matrix->saveBlock($block);
        }
        return false;
    }

}
