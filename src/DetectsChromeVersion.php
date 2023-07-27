<?php

namespace Staudenmeir\DuskUpdater;

use Symfony\Component\Process\Process;

trait DetectsChromeVersion
{
    /**
     * The default commands to detect the installed Chrome/Chromium version.
     *
     * @var array
     */
    protected static $platforms = [
        'linux' => [
            'slug' => 'linux64',
            'commands' => [
                '/usr/bin/google-chrome --version',
                '/usr/bin/chromium-browser --version',
                '/usr/bin/chromium --version',
                '/usr/bin/google-chrome-stable --version',
            ],
        ],
        'mac' => [
            'slug' => 'mac-x64',
            'commands' => [
                '/Applications/Google\ Chrome\ for\ Testing.app/Contents/MacOS/Google\ Chrome\ for\ Testing --version',
                '/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --version',
            ],
        ],
        'win' => [
            'slug' => 'win32',
            'commands' => [
                'reg query "HKEY_CURRENT_USER\Software\Google\Chrome\BLBeacon" /v version',
            ],
        ],
    ];

    /**
     * Detect the installed Chrome/Chromium version.
     *
     * @param string $os
     * @return int|bool
     */
    protected function chromeVersion($os)
    {
        $path = $this->option('detect');

        if ($path) {
            if ($os === 'win') {
                $this->error('Chrome version cannot be detected in custom installation path on Windows.');

                return false;
            }

            $commands = [$path.' --version'];
        } else {
            $commands = static::$platforms[$os]['commands'];
        }

        foreach ($commands as $command) {
            $process = Process::fromShellCommandline($command);

            $process->run();

            preg_match('/(\d+)(\.\d+){3}/', $process->getOutput(), $matches);

            if (!isset($matches[1])) {
                continue;
            }

            $this->comment(
                sprintf('Chrome version %s detected.', $matches[0])
            );

            return (int) $matches[1];
        }

        $this->error('Chrome version could not be detected. Please submit an issue: https://github.com/staudenmeir/dusk-updater');

        return false;
    }

    /**
     * Resolve the ChromeDriver slug for the given operating system.
     *
     * @param string $operatingSystem
     * @param string|null $version
     * @return string
     */
    public static function chromeDriverSlug($operatingSystem, $version = null)
    {
        $slug = static::$platforms[$operatingSystem]['slug'] ?? null;

        if (!is_null($version) && version_compare($version, '115.0', '<') && $slug === 'mac-x64') {
            return 'mac64';
        }

        return $slug;
    }

    /**
     * Get all supported operating systems.
     *
     * @return array
     */
    public static function all()
    {
        return array_keys(static::$platforms);
    }
}
