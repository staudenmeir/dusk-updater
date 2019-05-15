<?php

namespace Tests;

use Orchestra\Testbench\TestCase as Base;
use Staudenmeir\DuskUpdater\DuskServiceProvider;

abstract class TestCase extends Base
{
    protected function tearDown(): void
    {
        @unlink(__DIR__.'/bin/chromedriver-linux');
        @unlink(__DIR__.'/bin/chromedriver-mac');
        @unlink(__DIR__.'/bin/chromedriver-win.exe');

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [DuskServiceProvider::class];
    }
}
