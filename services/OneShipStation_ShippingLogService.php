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
        $blockTypes = craft()->matrix->getBlockTypesByFieldId($infoMatrix->id);
        $blockType  = array_shift($blockTypes);

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
     *       Override or declare your own by declaring `function oneShipStation_trackingURL($shippingInfo)` in your plugin.
     *       If you override, ensure the URL is encoded/escaped properly.
     */
    public function trackingURL($shippingInfo) {
        if ($override = craft()->plugins->callFirst('oneShipStation_trackingURL', [$shippingInfo])) {
            return $override;
        }
        $safeTrackingNumber = urlencode($shippingInfo->trackingNumber);
        switch (strtolower($shippingInfo->carrier)) {
            case 'ups':
                return "http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums={$safeTrackingNumber}";
            case 'usps':
                return "https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1={$safeTrackingNumber}";
            case 'fedex':
            case 'fedexinternationalmailservice':
                return "http://www.fedex.com/Tracking?action=track&tracknumbers={$safeTrackingNumber}";
            default: return null;
        }
    }
}
