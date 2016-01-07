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

        $order_mapping = ['OrderID'         => 'id',
                          'OrderNumber'     => 'number',
                          'OrderStatus'     => 'orderStatusId',
                          'OrderTotal'      => ['callback' => function($order) { return round($order->totalPrice, 2); },
                                                'cdata' => false],
                          'TaxAmount'       => ['field' => 'totalTax',
                                                'cdata' => false],
                          'ShippingAmount'  => ['field' => 'totalShippingCost',
                                                'cdata' => false]
        ];
        $this->mapCraftModel($order_xml, $order_mapping, $order);

        if ($dateOrderedObj = $order->dateOrdered)
            $order_xml->addChild('OrderDate', date_format($dateOrderedObj, 'n/j/Y H:m'));

        if ($lastModifiedObj = $order->datePaid)
            $order_xml->addChild('LastModified', date_format($lastModifiedObj, 'n/j/Y H:m'));

        if ($shippingObj = $order->shippingMethod)
            $order_xml->addChild('ShippingMethod', $this->cdata($shippingObj->handle));
        
        if ($paymentObj = $order->paymentMethod)
            $order_xml->addChild('PaymentMethod', $this->cdata($paymentObj->name));

        $item_xml = $this->items($order_xml, $order->getLineItems());

        $customer_xml = $this->customer($order_xml, $order->getCustomer());
        $this->address($customer_xml, $order->getBillingAddress(), 'BillTo');
        $this->address($customer_xml, $order->getShippingAddress(), 'ShipTo');

        return $order_xml;
    }

    /**
     * Build an XML document given an array of items
     *
     * @param SimpleXMLElement $xml the xml to add a child to or modify
     * @param [Commerce_LineItemModel] $items
     * @param String $name the name of the child node, default 'Items'
     * @return SimpleXMLElement
     */
    public function items(\SimpleXMLElement $xml, $items, $name='Items') {
        $items_xml = $xml->getName() == $name ? $xml : $xml->addChild($name);
        foreach ($items as $item) {
            $this->item($items_xml, $item);
        }

        return $xml;
    }

    /**
     * Build an XML document given a Commerce_LineItemModel instance
     *
     * @param SimpleXMLElement $xml the xml to add a child to or modify
     * @param Commerce_LineItemModel $item
     * @param String $name the name of the child node, default 'Item'
     * @return SimpleXMLElement
     */
    public function item(\SimpleXMLElement $xml, Commerce_LineItemModel $item, $name='Item') {
        $item_xml = $xml->getName() == $name ? $xml : $xml->addChild($name);

        $item_mapping = ['SKU'              => 'id',
                         'Name'             => 'description',
                         'Weight'           => ['callback' => function($item) { return round($item->weight, 2); },
                                                'cdata' => false],
                         'Quantity'         => ['field' => 'qty',
                                                'cdata' => false],
                         'UnitPrice'        => ['callback' => function($item) { return round($item->price, 2); },
                                                'cdata' => false],
                         'Adjustment'      =>  ['field' => 'onSale',
                                                'cdata' => false]
        ];
        $this->mapCraftModel($item_xml, $item_mapping, $item);
 
        $item_xml->addChild('WeightUnits', 'Grams');

        if (isset($item->snapshot['options'])) {
            $option_xml = $this->options($item_xml, $item->snapshot['options']);
        }

        return $item_xml;
    }

    /**
     * Build an XML document given a hash of options
     *
     * @param SimpleXMLElement $xml the xml to add a child to or modify
     * @param array $options
     * @param String $name the name of the child node, default 'Options'
     * @return SimpleXMLElement
     */
    public function options(\SimpleXMLElement $xml, $options, $name='Options') {
        $options_xml = $xml->getName() == $name ? $xml : $xml->addChild($name);

        foreach ($options as $key => $value) {
            $option_xml = $options_xml->addChild('Option');
            $option_xml->addChild('Name', $this->cdata($key));
            $option_xml->addChild('Value', $this->cdata($value));
        }

        return $xml;
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

        $customer_mapping = ['CustomerCode' => 'id',
                             'Email'        => 'email'];
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
            $address_mapping = ['Name'       => function($address) { return "{$address->firstName} {$address->lastName}"; },
                                'Company'    => 'businessName',
                                'Phone'      => 'phone',
                                'Address1'   => 'address1',
                                'Address2'   => 'address2',
                                'City'       => 'city',
                                'State'      => 'stateText',
                                'PostalCode' => 'zipCode',
                                'Country'    => 'countryText'
            ];
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

        //if field name exists in the options array
        if (is_array($options) && array_key_exists('field', $options)) {
            $field = $options['field'];
            $value = $model->{$field};
        }
        //if value is coming from a callback in the options array
        else if (is_array($options) && array_key_exists('callback', $options)) {
            $callback = $options['callback'];
            $value = $callback($model);
        }
        //if value is a callback
        else if (is_object($options) && is_callable($options)) {
            $value = $options($model);
        }
        //if value is an attribute on the model, passed as a string field name
        else if (!is_array($options)) {
            $value = $model->{$options};
        }

        if ($value === true || $value === false) {
            $value = ($value) ? "true" : "false";
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
