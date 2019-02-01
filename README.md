[![Latest Stable Version](https://poser.pugx.org/staudenmeir/dusk-updater/v/stable)](https://packagist.org/packages/staudenmeir/dusk-updater)
[![Total Downloads](https://poser.pugx.org/staudenmeir/dusk-updater/downloads)](https://packagist.org/packages/staudenmeir/dusk-updater)
[![License](https://poser.pugx.org/staudenmeir/dusk-updater/license)](https://packagist.org/packages/staudenmeir/dusk-updater)

## Introduction

This Artisan command updates your Laravel Dusk ChromeDriver binaries to the latest or specified release.  
Supports all versions of Dusk.

## Installation

    composer require --dev staudenmeir/dusk-updater

Users of Laravel 5.4 have to register the new provider in `AppServiceProvider::register()`:

```php
if ($this->app->environment('local', 'testing')) {
    $this->app->register(\Staudenmeir\DuskUpdater\DuskServiceProvider::class);
}
```

## Usage

    php artisan dusk:update
    
By default, you receive the latest ChromeDriver release.

You can also specify the major Chrome/Chromium version you are using:

     php artisan dusk:update 64

Or you directly specify the desired ChromeDriver version:

     php artisan dusk:update 2.37
     
If Dusk is still using the previous version after the update, there is probably an old ChromeDriver process running. 