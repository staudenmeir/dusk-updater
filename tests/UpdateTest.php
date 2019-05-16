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

    public function testChromeVersionDetection()
    {
        $version = $this->latestVersion();

        $this->artisan('dusk:update', ['--detect' => null])
            ->expectsOutput('Chrome version '.$this->chromeVersion().' detected.')
            ->expectsOutput('ChromeDriver binary successfully updated to version '.$version.'.')
            ->assertExitCode(0);

        $this->assertStringContainsString($version, shell_exec(__DIR__.'/bin/chromedriver-linux --version'));
        $this->assertTrue(is_executable(__DIR__.'/bin/chromedriver-linux'));
        $this->assertFalse(file_exists(__DIR__.'/bin/chromedriver-mac'));
        $this->assertFalse(file_exists(__DIR__.'/bin/chromedriver-win.exe'));
    }

    public function testChromeVersionDetectionWithPath()
    {
        $version = $this->latestVersion();

        $this->artisan('dusk:update', ['--detect' => '/usr/bin/google-chrome'])
            ->expectsOutput('Chrome version '.$this->chromeVersion().' detected.')
            ->expectsOutput('ChromeDriver binary successfully updated to version '.$version.'.')
            ->assertExitCode(0);

        $this->assertStringContainsString($version, shell_exec(__DIR__.'/bin/chromedriver-linux --version'));
        $this->assertTrue(is_executable(__DIR__.'/bin/chromedriver-linux'));
        $this->assertFalse(file_exists(__DIR__.'/bin/chromedriver-mac'));
        $this->assertFalse(file_exists(__DIR__.'/bin/chromedriver-win.exe'));
    }

    public function testChromeVersionDetectionWithInvalidPath()
    {
        $this->artisan('dusk:update', ['--detect' => '/dev/null'])
            ->expectsOutput('Chrome version could not be detected. Please submit an issue: https://github.com/staudenmeir/dusk-updater')
            ->assertExitCode(1);
    }

    /**
     * Get the installed Chrome version.
     *
     * @return string
     */
    protected function chromeVersion()
    {
        preg_match('/[\d.]+/', `google-chrome --version`, $matches);

        return $matches[0];
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
