# One ShipStation

Integrate Craft Commerce with ShipStation.

## Installation

Add OneShipStation to your `composer.json` file:

```
{
    "repositories": [
        {
            "type": "git",
            "url": "git@github.com:onedesign/oneshipstation.git"
        }
    ],

    "require": {
        "onedesign/oneshipstation": "0.1",
    }
}
```

Then run `composer install`. Go to the Craft Control Panel to install and configure.

## Testing

Run tests using PHPUnit, as installed using composer.

```
$ composer update --dev
$ vendor/bin/phpunit tests/
```

Note that running a version of phpunit installed elsewhere in your `$PATH` may break. So use the one installed in the `vendor/bin/` directory.

## Development

On any Craft project, navigate to `craft/plugins` and clone the repository:

```
$ cd craft/plugins
$ git clone git@github.com:onedesign/oneshipstation.git
```

Be sure to add `craft/plugins/oneshipstation` to your other project's gitignore, if applicable:

```
# .gitignore
craft/plugins/oneshipstation
```

## ShipStation Configuration

### Authentication

Once you have configured your Craft application's OneShipStation, you will need to complete the process by configuring your [ShipStation "Custom Store" integration](https://help.shipstation.com/hc/en-us/articles/205928478-ShipStation-Custom-Store-Development-Guide#3a).

There, you will be required to provide a user name, password, and a URL that ShipStation will use to contact your application.

### Custom Fields & Internal Notes

Shipstation allows the addition of up to three custom fields per order. This appear to the user as `OrderCustomField1`, `OrderCustomField2`, and `OrderCustomField3` for custom fields, and `InternalNotes`.

You can populate these fields by ["latching on" to a Craft hook](https://craftcms.com/docs/plugins/hooks-and-events#latching-onto-hooks) in your plugin.

Your plugin should return a callback that takes a single parameter `$order`, which is the order instance. It should return a single value.

In this example, the plugin `MyPlugin` will send the value `my custom value` to all orders in the `OrderCustomField1`:

```
class MyPlugin extends BasePlugin {
    //...
    public function oneShipStationCustomField1() {
        return function($order) {
            return 'my custom value';
        };
    }
}
```

Note: OneShipStation will add a `CustomFieldX` child for each plugin that responds to the hook.

For internal notes, if a plugin responds to the hook at all, the key will be added. Respond as:

```
class MyPlugin extends BasePlugin {
    //...
    public function oneShipStationInternalNotes() {
        return function($order) {
            return 'internal notes for this order';
        };
    }
}
```

### Installation Requirements

#### CSRF Protection

If you have CSRF protection enabled in your app, you will need to disable it for when ShipStation POSTs shipment notifications.

In `craft/config/general.php`, if you have `enableCsrfProtection` set to true (or, in Craft 3+, if you _don't_ have it set to false), you will need to add the following:

```
return array(
    //...
    'enableCsrfProtection' => !isset($_REQUEST['p']) || $_REQUEST['p'] != '/actions/oneShipStation/orders/process'
)
```

This will ensure that CSRF protection is enabled for all routes that are NOT the route ShipStation posts to.

#### "Action" naming collision

ShipStation and Craft have a routing collision due to their combined use of the parameter `action`.
ShipStation sends requests using `?action=export` to read order data, and `?action=shipnotify` to update shipping data.
This conflicts with Craft's reserved word `action` to describe an ["action request"](https://craftcms.com/docs/plugins/controllers#how-controller-actions-fit-into-routing),
which is designed to allow for easier routing configuration.

Because of this, the route given to ShipStation for their Custom Store integration _must_ begin with your Craft config's "actionTrigger" (in `craft/config/general.php`), which defaults to the string "actions".

For example, if your actionTrigger is set to "actions", the URL you prove to ShipStation should be:

```
https://{yourdomain.com}/actions/oneShipStation/orders/process
```

If your actionTrigger is set to "myCustomActionTrigger", it would be:

```
https://{yourdomain.com}/myCustomActionTrigger/oneShipStation/orders/process
```

#### Case Sensitivity

Note that this is case sensitive. Due to Craft's segment matching, the `oneShipStation` segment in the URL _must_ be `oneShipStation`, not `oneshipstation` or `ONESHIPSTATION`.

### Miscellaneous

#### Getting Tracking Information in a Template

One ShipStation provides a helper method to add to your template to provide customers with a link to track their shipment.

```
{% for shipmentInfo in order.shippingInfo %}
  {% set tracking = craft.oneShipStation.trackingNumberLinkHTML(shipmentInfo) %}
  {% if tracking|length %}
    Track shipment: {{ tracking|raw }}
  {% endif %}
{% endfor %}
```

Currently One ShipStation only provides links for common carriers. If your carrier is not defined, or if you want a different URL, you can override:

```
class MyPlugin extends BasePlugin {
    public function oneShipStation_trackingURL($shippingInfo) {
        return 'https://mycustomlink?tracking=' . urlencode($shippingInfo->trackingNumber);
    }
}
```
