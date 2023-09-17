<?php

namespace Tests;

class UpdateTest extends TestCase
{
    public function testLatestVersion()
    {
        $version = $this->driverVersion();

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

    public function testInvalidChromeVersion()
    {
        $this->artisan('dusk:update', ['version' => '999999'])
            ->expectsOutput('Could not determine the ChromeDriver version.')
            ->assertExitCode(1);
    }

    public function testChromeVersionDetection()
    {
        $chromeVersion = $this->chromeVersion();

        $version = $this->driverVersion(explode('.', $chromeVersion)[0]);

        $this->artisan('dusk:update', ['--detect' => null])
            ->expectsOutput('Chrome version '.$chromeVersion.' detected.')
            ->expectsOutput('ChromeDriver binary successfully updated to version '.$version.'.')
            ->assertExitCode(0);

        $this->artisan('dusk:update', ['--detect' => null])
            ->expectsOutput('Chrome version '.$chromeVersion.' detected.')
            ->expectsOutput('No update necessary, your ChromeDriver binary is already on version '.$version.'.')
            ->assertExitCode(0);

        $this->assertStringContainsString($version, shell_exec(__DIR__.'/bin/chromedriver-linux --version'));
        $this->assertTrue(is_executable(__DIR__.'/bin/chromedriver-linux'));
        $this->assertFalse(file_exists(__DIR__.'/bin/chromedriver-mac'));
        $this->assertFalse(file_exists(__DIR__.'/bin/chromedriver-win.exe'));
    }

    public function testChromeVersionDetectionWithPath()
    {
        $chromeVersion = $this->chromeVersion();

        $version = $this->driverVersion(explode('.', $chromeVersion)[0]);

        $this->artisan('dusk:update', ['--detect' => '/usr/bin/google-chrome'])
            ->expectsOutput('Chrome version '.$chromeVersion.' detected.')
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
     * Get the latest stable or a specific ChromeDriver version.
     *
     * @param int|null $major
     * @return string
     */
    protected function driverVersion($major = null)
    {
        if (is_null($major)) {
            $versions = json_decode(
                file_get_contents(
                    'https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json'
                ),
                true
            );

            return $versions['channels']['Stable']['version'];
        }

        if ($major < 115) {
            $url = "https://chromedriver.storage.googleapis.com/LATEST_RELEASE_$major";

            return trim((string) file_get_contents($url));
        }

        $milestones = json_decode(
            file_get_contents(
                'https://googlechromelabs.github.io/chrome-for-testing/latest-versions-per-milestone-with-downloads.json'
            ),
            true
        );

        return $milestones['milestones'][$major]['version'];
    }
}
