<?php
namespace Craft;

class OneShipStation_XmlService extends BaseApplicationComponent {

    public function shouldInclude($order) {
        return $order->getShippingAddress() && $order->getBillingAddress();
    }

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
            if ($this->shouldInclude($order)) {
                $this->order($orders_xml, $order);
            }
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

        $order_mapping = ['OrderID'         => ['callback' => function($order) {
                                                   $settings =  craft()->plugins->getPlugin('oneShipStation')->getSettings();
                                                   $prefix = $settings->order_id_prefix;
                                                   return $prefix . $order->id;
                                               }],
                          'OrderNumber'     => 'number',
                          'OrderStatus'     => ['callback' => function($order) { return $order->getOrderStatus()->handle; }],
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
            $this->addChildWithCDATA($order_xml, 'ShippingMethod', $shippingObj->handle);
        
        if ($paymentObj = $order->paymentMethod)
            $this->addChildWithCDATA($order_xml, 'PaymentMethod', $paymentObj->name);

        $item_xml = $this->items($order_xml, $order->getLineItems());

        $customer = $order->getCustomer();
        $customer_xml = $this->customer($order_xml, $customer);

        $billTo_xml = $this->billTo($customer_xml, $order, $customer);

        $shipTo_xml = $this->shipTo($customer_xml, $order, $customer);

        $this->customOrderFields($order_xml, $order);

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

        $item_mapping = ['SKU'              => ['callback' => function($item) { return $item->getPurchasable()->sku; }],
                         'Name'             => 'description',
                         'Weight'           => ['callback' => function($item) { return round($item->weight, 2); },
                                                'cdata' => false],
                         'Quantity'         => ['field' => 'qty',
                                                'cdata' => false],
                         'UnitPrice'        => ['callback' => function($item) { return round($item->salePrice, 2); },
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
            $this->addChildWithCDATA($option_xml, 'Name', $key);
            $this->addChildWithCDATA($option_xml, 'Value', $value);
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

        $customer_mapping = ['CustomerCode' => 'id'];
        $this->mapCraftModel($customer_xml, $customer_mapping, $customer);

        return $customer_xml;
    }

    /**
     * Add a BillTo address XML Child
     *
     * @param SimpleXMLElement $customer_xml the xml to add a child to or modify
     * @param Commerce_OrderModel $order
     * @param Commerce_CustomerModel $customer
     * @return SimpleXMLElement, or null if no address exists
     */
    public function billTo(\SimpleXMLElement $customer_xml, Commerce_OrderModel $order, Commerce_CustomerModel $customer) {
        if ($billingAddress = $order->getBillingAddress()) {
            $billTo_xml = $this->address($customer_xml, $billingAddress, 'BillTo');
            if ($billingAddress->firstName && $billingAddress->lastName) {
                $name = "{$billingAddress->firstName} {$billingAddress->lastName}";
            } else {
                $user = $customer->getUser();
                $name = ($user->firstName && $user->lastName) ? "{$user->firstName} {$user->lastName}" : 'unknown';
            }
            $this->addChildWithCDATA($billTo_xml, 'Name', $name);
            $billTo_xml->addChild('Email', $customer->email);

            return $billTo_xml;
        }
        return null;
    }

    /**
     * Add a ShipTo address XML Child
     *
     * @param SimpleXMLElement $customer_xml the xml to add a child to or modify
     * @param Commerce_OrderModel $order
     * @param Commerce_CustomerModel $customer
     * @return SimpleXMLElement, or null if no address exists
     */
    public function shipTo(\SimpleXMLElement $customer_xml, Commerce_OrderModel $order, Commerce_CustomerModel $customer) {
        $shippingAddress = $order->getShippingAddress();
        $shipTo_xml = $this->address($customer_xml, $shippingAddress, 'ShipTo');
        if ($shippingAddress->firstName && $shippingAddress->lastName) {
            $name = "{$shippingAddress->firstName} {$shippingAddress->lastName}";
        } else {
            $user = $customer->getUser();
            $name = ($user->firstName && $user->lastName) ? "{$user->firstName} {$user->lastName}" : 'unknown';
        }
        $this->addChildWithCDATA($shipTo_xml, 'Name', $name);

        return $shipTo_xml;
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
            $address_mapping = ['Company'    => 'businessName',
                                'Phone'      => 'phone',
                                'Address1'   => 'address1',
                                'Address2'   => 'address2',
                                'City'       => 'city',
                                'State'      => 'stateText',
                                'PostalCode' => 'zipCode',
                                'Country'    =>  ['callback' => function($address) { return $address->countryId ? $address->getCountry()->iso : null; },
                                                  'cdata'    => false]
            ];
            $this->mapCraftModel($address_xml, $address_mapping, $address);
        }

        return $address_xml;
    }

    /**
     * Allow plugins to add custom fields to the order
     *
     * @param SimpleXMLElement $xml the order xml to add a child
     * @param Commerce_OrderModel $order
     * @return SimpleXMLElement
     */
    public function customOrderFields(\SimpleXMLElement $order_xml, Commerce_OrderModel $order) {
        $customFields = ['CustomField1', 'CustomField2', 'CustomField3', 'InternalNotes'];
        foreach ($customFields as $fieldName) {
            if ($customFieldCallbacks = craft()->plugins->call("oneShipStation{$fieldName}")) {
                foreach ($customFieldCallbacks as $callback) {
                    if (is_callable($callback)) {
                        $value = $callback($order);
                        $order_xml->addChild($fieldName, $value);
                    }
                }
            }
        }
        return $order_xml;
    }

    /***************************** helpers *******************************/

    protected function mapCraftModel($xml, $mapping, $model) {
        foreach ($mapping as $name => $attr) {
            $value = $this->valueFromMappingAndModel($attr, $model);

            //wrap in cdata unless explicitly set not to
            if (!is_array($attr) || !array_key_exists('cdata', $attr) || $attr['cdata']) {
                $this->addChildWithCDATA($xml, $name, $value);
            } else {
                $xml->addChild($name, $value);
            }
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
            $value = $value ? "true" : "false";
        }
        return $value;
    }

    /**
     * Add a child with <!CDATA[...]]>
     *
     * We cannot simply do this by manipulating the string, because SimpleXMLElement and/or Craft will encode it
     *
     * @param $xml SimpleXMLElement the parent to which we're adding a child
     * @param $name String the xml node name
     * @param $value Mixed the value of the new child node, which will be wrapped in CDATA
     * @return SimpleXMLElement, the new child
     */
    protected function addChildWithCDATA(&$xml, $name, $value) {
        $new_child = $xml->addChild($name);
        if ($new_child !== NULL) {
            $node = dom_import_simplexml($new_child);
            $node->appendChild($node->ownerDocument->createCDATASection($value));
        }
        return $new_child;
    }
}
