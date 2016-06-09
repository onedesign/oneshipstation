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

    /**
     * Return a URL for tracking shipments
     *
     * Note: This is by no means an exhaustive list of services!
     *       Override or declare your own by declaring `function oneShipStation_trackingURL($shippingInfo)` in your plugin
     */
    public function trackingURL($shippingInfo) {
        $trackingNumber = $shippingInfo->trackingNumber;
        if ($override = craft()->plugins->callFirst('oneShipStation_trackingURL', [$shippingInfo])) {
            return $override;
        }
        switch (strtolower($shippingInfo->carrier)) {
            case 'ups':
                return "http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums={$trackingNumber}";
            case 'usps':
                return "https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1={$trackingNumber}";
            case 'fedex':
            case 'fedexinternationalmailservice':
                return "http://www.fedex.com/Tracking?action=track&tracknumbers={$trackingNumber}";
            default: return null;
        }
    }
}
