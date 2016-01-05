<?php
namespace Craft;

class Oneshipstation_IntegrationsController extends BaseController
{
	/**
     * @param date $start
     * @param date $end
     *
     * @return Commerce_OrderModel[]|null
     */
	public function getOrdersBetween($start, $end) {
		$criteria = craft()->elements->getCriteria('Commerce_Order');
		$startText = date_format($start, 'Y-m-d H:i:s');
		$endText = date_format($end, 'Y-m-d H:i:s');
		$criteria->dateOrdered = array('and', '> '.$startText, '< '.$endText);
		return $criteria->find();
	}

}