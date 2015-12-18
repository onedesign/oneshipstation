<?php
namespace Craft;

class OneShipStation_XmlServiceTest extends BaseTest
{
    public function setup() {
        parent::setup();

        craft()->plugins->loadPlugins();
    }

    public function testModelMappingByFieldNameReturnsCorrectFieldValue() {
        $object = new \StdClass();
        $object->local_key = md5(time());

        $cdata_method = $this->getMethod(craft()->oneShipStation_xml, 'valueFromMappingAndModel');
        $returned_value = $cdata_method->invoke(craft()->oneShipStation_xml, 'local_key', $object);

        $this->assertEquals($returned_value, '<![CDATA[' . $object->local_key . ']]>');
    }

    public function testModelMappingByHashReturnsCorrectFieldValue() {
        $object = new \StdClass();
        $object->local_key = md5(time());

        $cdata_method = $this->getMethod(craft()->oneShipStation_xml, 'valueFromMappingAndModel');
        $returned_value = $cdata_method->invoke(craft()->oneShipStation_xml, ['field' => 'local_key'], $object);

        $this->assertEquals($returned_value, '<![CDATA[' . $object->local_key . ']]>');
    }

    public function testModelMappingWithNoCDATAReturnsCorrectFieldValue() {
        $object = new \StdClass();
        $object->local_key = md5(time());

        $cdata_method = $this->getMethod(craft()->oneShipStation_xml, 'valueFromMappingAndModel');
        $returned_value = $cdata_method->invoke(craft()->oneShipStation_xml, ['cdata' => false, 'field' => 'local_key'], $object);

        $this->assertEquals($returned_value, $object->local_key);
    }

    public function testCDATAFormatWithStrings() {
        $test_value = "here is the test value with <special>[]' characters>>!";

        $cdata_method = $this->getMethod(craft()->oneShipStation_xml, 'cdata');
        $escaped_value = $cdata_method->invoke(craft()->oneShipStation_xml, $test_value);

        $this->assertContains('CDATA', $escaped_value, 'Escaped value is invalid CDATA string: "' . $escaped_value . '"');
        $this->assertRegExp('/<!\[CDATA\[.+\]\]>/', $escaped_value, 'Escaped value fails to match expected Regex');
    }
}
