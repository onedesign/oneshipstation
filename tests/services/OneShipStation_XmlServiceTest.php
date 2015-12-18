<?php
namespace Craft;

class OneShipStation_XmlServiceTest extends BaseTest
{
    public function setup() {
        parent::setup();

        craft()->plugins->loadPlugins();
    }

    public function testCDATAFormatWithStrings() {
        $test_value = "here is the test value with <special>[]' characters>>!";

        $cdata_method = $this->getMethod(craft()->oneShipStation_xml, 'cdata');
        $escaped_value = $cdata_method->invoke(craft()->oneShipStation_xml, $test_value);

        $this->assertContains('CDATA', $escaped_value, 'Escaped value is invalid CDATA string: "' . $escaped_value . '"');
        $this->assertRegExp('/<!\[CDATA\[.+\]\]>/', $escaped_value, 'Escaped value fails to match expected Regex');
    }
}
