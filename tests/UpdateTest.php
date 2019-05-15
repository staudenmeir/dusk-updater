<?php

namespace Tests;

class UpdateTest extends TestCase
{
    public function testLatestVersion()
    {
        $version = $this->latestVersion();

        $this->artisan('dusk:update')
            ->expectsOutput('ChromeDriver binaries successfully updated to version '.$version.'.')
            ->assertExitCode(0);

        $this->assertStringContainsString($version, shell_exec(__DIR__.'/bin/chromedriver-linux --version'));
        $this->assertTrue(is_executable(__DIR__.'/bin/chromedriver-linux'));
        $this->assertTrue(file_exists(__DIR__.'/bin/chromedriver-mac'));
        $this->assertTrue(file_exists(__DIR__.'/bin/chromedriver-win.exe'));
    }

    public function testChromeVersion()
    {
        $this->artisan('dusk:update', ['version' => '73'])
            ->expectsOutput('ChromeDriver binaries successfully updated to version 73.0.3683.68.')
            ->assertExitCode(0);

        $this->assertStringContainsString('73.0.3683.68', shell_exec(__DIR__.'/bin/chromedriver-linux --version'));
        $this->assertTrue(is_executable(__DIR__.'/bin/chromedriver-linux'));
        $this->assertTrue(file_exists(__DIR__.'/bin/chromedriver-mac'));
        $this->assertTrue(file_exists(__DIR__.'/bin/chromedriver-win.exe'));
    }

    public function testLegacyChromeVersion()
    {
        $this->artisan('dusk:update', ['version' => '69'])
            ->expectsOutput('ChromeDriver binaries successfully updated to version 2.44.')
            ->assertExitCode(0);

        $this->assertStringContainsString('2.44', shell_exec(__DIR__.'/bin/chromedriver-linux --version'));
        $this->assertTrue(is_executable(__DIR__.'/bin/chromedriver-linux'));
        $this->assertTrue(file_exists(__DIR__.'/bin/chromedriver-mac'));
        $this->assertTrue(file_exists(__DIR__.'/bin/chromedriver-win.exe'));
    }

    public function testChromeDriverVersion()
    {
        $this->artisan('dusk:update', ['version' => '73.0.3683.68'])
            ->expectsOutput('ChromeDriver binaries successfully updated to version 73.0.3683.68.')
            ->assertExitCode(0);

        $this->assertStringContainsString('73.0.3683.68', shell_exec(__DIR__.'/bin/chromedriver-linux --version'));
        $this->assertTrue(is_executable(__DIR__.'/bin/chromedriver-linux'));
        $this->assertTrue(file_exists(__DIR__.'/bin/chromedriver-mac'));
        $this->assertTrue(file_exists(__DIR__.'/bin/chromedriver-win.exe'));
    }

    /**
     * Get the latest stable ChromeDriver version.
     *
     * @return string
     */
    protected function latestVersion()
    {
        $home = file_get_contents('http://chromedriver.chromium.org/home');

        preg_match('/Latest stable release:.*?\?path=([\d.]+)/', $home, $matches);

        return $matches[1];
    }
}
