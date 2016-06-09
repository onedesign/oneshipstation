<?php
namespace Craft;

class OneShipStationVariable {

    /**
     * Generates a link with based on the shipping carrier and tracking number
     *
     * Note: This is not an exhaustive list of carriers
     */
    public function trackingNumberLinkHTML($shippingInfo) {
        if (!$shippingInfo) {
            return '';
        }
        if ($url = craft()->oneShipStation_shippingLog->trackingURL($shippingInfo)) {
            return '<a target="blank" href="' . $url . '">' . $shippingInfo->trackingNumber . '</a>';
        }
        return $shippingInfo->trackingNumber;
    }

}
