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

Once you have configured your Craft application's OneShipStation, you will need to complete the proces by configuring your [ShipStation "Custom Store" integration](https://help.shipstation.com/hc/en-us/articles/205928478-ShipStation-Custom-Store-Development-Guide#3a).

There, you will be required to provide a user name, password, and a URL that ShipStation will use to contact your application.

### Installation Requirements

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
