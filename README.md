## Introduction

This Artisan command updates your Laravel Dusk ChromeDriver binaries to the latest release.  
It supports all versions of Dusk.

## Installation

    composer require --dev staudenmeir/dusk-updater:^1.0

Users of Laravel 5.4 have to register the new provider in `AppServiceProvider::register()`:

    if ($this->app->environment('local', 'testing')) {
        $this->app->register(\Staudenmeir\DuskUpdater\DuskServiceProvider::class);
    }

## Usage

    php artisan dusk:update