<?php
namespace Craft;

class OneShipStation_XmlService extends BaseApplicationComponent {

    /**
     * Build an XML document given an array of orders
     *
     * @param SimpleXMLElement $xml the xml to add a child to or modify
     * @param [Commerce_OrderModel] $orders
     * @param String $name the name of the child node, default 'Orders'
     * @return SimpleXMLElement
     */
    public function orders(\SimpleXMLElement $xml, $orders, $name='Orders') {
        $orders_xml = $xml->getName() == $name ? $xml : $xml->addChild($name);
        foreach ($orders as $order) {
            $this->order($orders_xml, $order);
        }

        return $xml;
    }

    /**
     * Build an XML document given a Commerce_OrderModel instance
     *
     * @param SimpleXMLElement $xml the xml to add a child to or modify
     * @param Commerce_OrderModel $order
     * @param String $name the name of the child node, default 'Order'
     * @return SimpleXMLElement
     */
    public function order(\SimpleXMLElement $xml, Commerce_OrderModel $order, $name='Order') {
        $order_xml = $xml->getName() == $name ? $xml : $xml->addChild($name);

        $order_mapping = ['OrderID'     => 'id',
                          'OrderNumber' => 'number',
                          'OrderTotal'  => ['field' => 'totalPrice',
                                            'cdata' => false], //TODO confirm
         ];
        $this->mapCraftModel($order_xml, $order_mapping, $order);

        $customer_xml = $this->customer($order_xml, $order->getCustomer());
        $this->address($customer_xml, $order->getBillingAddress(), 'BillTo');
        $this->address($customer_xml, $order->getShippingAddress(), 'ShipTo');

        return $order_xml;
    }

    /**
     * Build an XML document given a Commerce_CustomerModel instance
     *
     * @param SimpleXMLElement $xml the xml to add a child to or modify
     * @param Commerce_CustomerModel $customer
     * @param String $name the name of the child node, default 'Customer'
     * @return SimpleXMLElement
     */
    public function customer(\SimpleXMLElement $xml, Commerce_CustomerModel $customer, $name='Customer') {
        $customer_xml = $xml->getName() == $name ? $xml : $xml->addChild($name);

        $customer_mapping = ['CustomerCode' => 'id'];
        $this->mapCraftModel($customer_xml, $customer_mapping, $customer);

        return $customer_xml;
    }

    /**
     * Build an XML document given a Commerce_AddressModel instance
     *
     * @param SimpleXMLElement $xml the xml to add a child to or modify
     * @param Commerce_AddressModel $address
     * @param String $name the name of the child node, default 'Address'
     * @return SimpleXMLElement
     */
    public function address(\SimpleXMLElement $xml, Commerce_AddressModel $address=null, $name='Address') {
        $address_xml = $xml->getName() == $name ? $xml : $xml->addChild($name);

        if (!is_null($address)) {
            $address_mapping = ['Name'    => function($address) { return "{$address->firstName} {$address->lastName}"; },
                                'Company' => 'businessName'];
            $this->mapCraftModel($address_xml, $address_mapping, $address);
        }

        return $address_xml;
    }

    /***************************** helpers *******************************/

    protected function mapCraftModel($xml, $mapping, $model) {
        foreach ($mapping as $name => $attr) {
            $value = $this->valueFromMappingAndModel($attr, $model);
            $xml->addChild($name, $value);
        }
        return $xml;
    }

    /**
     * Retrieve data from a Craft model by field name or method name.
     *
     * Example usage:
     *   by field:
     *   $value = $this->valueFromMappingAndModel('id', $order);
     *   echo $value; // order id, wrapped in CDATA tag
     *
     *   by field with custom options
     *   $options = ['field' => 'totalAmount', 'cdata' => false];
     *   $value = $this->valueFromMappingAndModel($options, $value);
     *   echo $value; // the order's totalAmount, NOT wrapped in cdata
     *
     *   by annonymous function (closure):
     *   $value = $this->valueFromMappingAndModel(function($order) {
     *      return is_null($order->name) ? 'N/A' : $order->name;
     *   }, $order);
     *   echo $value; // the order's name if it is set, or 'N/A' otherwise
     *
     *   @param mixed $options, a string field name or
     *                          a callback accepting the model instance as its only parameter or
     *                          a hash containing options with a 'field' or 'callback' key
     *   @param BaseModel $model, an instance of a craft model
     *   @return string
     */
    protected function valueFromMappingAndModel($options, $model) {
        $value = null;
        if (is_array($options) && array_key_exists('field', $options)) {
            $field = $options['field'];
            $value = $model->{$field};
        } else if (is_array($options) && array_key_exists('callback', $options)) {
            $callback = $options['callback'];
            $value = $callback($model);
        } else if (is_object($options) && is_callable($options)) {
            $value = $options($model);
        } else if (!is_array($options)) {
            $value = $model->{$options};
        }

        //wrap in cdata unless explicitly set not to
        if (!is_array($options) || !array_key_exists('cdata', $options) || $options['cdata']) {
            $value = $this->cdata($value);
        }
        return $value;
    }

    protected function cdata($value) {
        return "<![CDATA[{$value}]]>";
    }

}
