[![Latest Stable Version](https://poser.pugx.org/moxio/captainhook-psalm/v/stable)](https://packagist.org/packages/moxio/captainhook-psalm)

moxio/captainhook-psalm
==================================
This project is a plugin for [CaptainHook](https://github.com/captainhookphp/captainhook) to check your staged PHP files
for errors using [Psalm](https://psalm.dev/). The commit is blocked when one or more errors is detected in any of the
staged PHP files.

Installation
------------
Install as a development dependency using composer:
```
$ composer require --dev moxio/captainhook-psalm
```

Usage
-----
Add Psalm error checking as a `pre-commit` to your `captainhook.json` configuration file:
```json
{
    "pre-commit": {
        "enabled": true,
        "actions": [
            {
                "action": "\\Moxio\\CaptainHook\\Psalm\\PsalmCheckAction"
            }
        ]
    }
}
```

The check is only run when committing changes to PHP files. It will thus not detect pre-existing duplications in PHP
files which are not staged.

The action expects [Psalm](https://github.com/vimeo/psalm) to be installed as a local Composer package (i.e. available
at `vendor/vimeo/psalm`).

### Conditional usage
If you want to perform Psalm-error checks only when Psalm is installed (i.e. available at
`vendor/vimeo/psalm`), you can add a corresponding condition to the action:
```json
{
    "pre-commit": {
        "enabled": true,
        "actions": [
            {
                "action": "\\Moxio\\CaptainHook\\Psalm\\PsalmCheckAction",
                "conditions": [
                    {
                        "exec": "\\Moxio\\CaptainHook\\Psalm\\Condition\\PsalmInstalled"
                    }
                ]
            }
        ]
    }
}
```
This may be useful in scenarios where you have a shared CaptainHook configuration file that is
[included](https://captainhookphp.github.io/captainhook/configure.html#includes) both in projects that use Psalm and
projects that don't. If Psalm is installed, the action is run. In projects without Psalm, the validation is skipped.

Versioning
----------
This project adheres to [Semantic Versioning](http://semver.org/).

Contributing
------------
Contributions to this project are welcome. Please make sure that your code follows the
[PSR-12](https://www.php-fig.org/psr/psr-12/) extended coding style.

License
-------
This project is released under the MIT license.
