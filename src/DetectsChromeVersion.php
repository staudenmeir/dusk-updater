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
    protected $chromeCommands = [
        'linux' => [
            '/usr/bin/google-chrome --version',
            '/usr/bin/chromium-browser --version',
        ],
        'mac' => [
            '/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --version',
        ],
        'win' => [
            'reg query "HKEY_CURRENT_USER\Software\Google\Chrome\BLBeacon" /v version',
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
            $commands = $this->chromeCommands[$os];
        }

        foreach ($commands as $command) {
            $process = new Process($command);

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
}
