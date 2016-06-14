<?php
namespace Craft;

class OneShipStationVariable {

    /**
     * Generates a link with based on the shipping carrier and tracking number.
     * Add this to any template with the raw filter:
     *    {{ tracking|raw }}
     *
     * Note: This is not an exhaustive list of carriers
     */
    public function trackingNumberLinkHTML($shippingInfo) {
        if (!$shippingInfo) {
            return '';
        }
        $safeTrackingNumber = htmlspecialchars($shippingInfo->trackingNumber);
        if ($url = craft()->oneShipStation_shippingLog->trackingURL($shippingInfo)) {
            return '<a target="blank" href="' . $url . '">' . $safeTrackingNumber . '</a>';
        }
        return $safeTrackingNumber;
    }

}
