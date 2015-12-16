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
