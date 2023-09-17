# Dusk Updater

[![CI](https://github.com/staudenmeir/dusk-updater/actions/workflows/ci.yml/badge.svg)](https://github.com/staudenmeir/dusk-updater/actions/workflows/ci.yml)
[![Code Coverage](https://scrutinizer-ci.com/g/staudenmeir/dusk-updater/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/staudenmeir/dusk-updater/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/staudenmeir/dusk-updater/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/staudenmeir/dusk-updater/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/staudenmeir/dusk-updater/v/stable)](https://packagist.org/packages/staudenmeir/dusk-updater)
[![Total Downloads](https://poser.pugx.org/staudenmeir/dusk-updater/downloads)](https://packagist.org/packages/staudenmeir/dusk-updater/stats)
[![License](https://poser.pugx.org/staudenmeir/dusk-updater/license)](https://github.com/staudenmeir/dusk-updater/blob/master/LICENSE)

This Artisan command updates your Laravel Dusk ChromeDriver binaries to the latest or specified release.

Supports all versions of Dusk.

## Installation

    composer require --dev staudenmeir/dusk-updater:"^1.1"

Users of Laravel 5.4 have to register the new provider in `AppServiceProvider::register()`:

```php
if ($this->app->environment('local', 'testing')) {
    $this->app->register(\Staudenmeir\DuskUpdater\DuskServiceProvider::class);
}
```

## Usage

Download the latest stable ChromeDriver release:

    php artisan dusk:update

Let the updater detect the installed Chrome/Chromium version:

    php artisan dusk:update --detect

Specify the absolute path to your custom Chrome/Chromium installation (not supported on Windows):

    php artisan dusk:update --detect=/usr/bin/google-chrome

Specify the major Chrome/Chromium version manually:

     php artisan dusk:update 74

Specify the desired ChromeDriver version manually:

     php artisan dusk:update 74.0.3729.6
     
If Dusk is still using the previous version after the update, there is probably an old ChromeDriver process running that you need to terminate first.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) and [CODE OF CONDUCT](.github/CODE_OF_CONDUCT.md) for details.
