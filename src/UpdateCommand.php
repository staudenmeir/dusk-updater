<?php

namespace Staudenmeir\DuskUpdater;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use ZipArchive;

class UpdateCommand extends Command
{
    use DetectsChromeVersion;

    /**
     * The file extensions of the ChromeDriver binaries.
     *
     * @var array
     */
    public static $extensions = [
        'linux' => '',
        'mac' => '',
        'mac-intel' => '',
        'mac-arm' => '',
        'win' => '.exe',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dusk:update {version?}
        {--detect= : Detect the installed Chrome/Chromium version, optionally in a custom path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the Dusk ChromeDriver binaries';

    /**
     * The path to the binaries directory.
     *
     * @var string
     */
    protected $directory = __DIR__.'/../../../laravel/dusk/bin/';

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        if (defined('DUSK_UPDATER_TEST')) {
            $this->directory = __DIR__.'/../tests/bin/';
        }

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $detect = $this->input->hasParameterOption('--detect');

        $os = $this->os();

        $version = $this->version($detect, $os);

        if ($version === false) {
            $this->error('Could not determine the ChromeDriver version.');

            return 1;
        }

        if ($detect && $this->checkVersion($os, $version)) {
            $this->info(
                sprintf('No update necessary, your ChromeDriver binary is already on version %s.', $version)
            );
        } else {
            $this->update($detect, $os, $version);

            $this->info(
                sprintf('ChromeDriver %s successfully updated to version %s.', $detect ? 'binary' : 'binaries', $version)
            );
        }

        return 0;
    }

    /**
     * Get the desired ChromeDriver version.
     *
     * @param bool $detect
     * @param string $os
     * @return string|bool
     */
    protected function version($detect, $os)
    {
        if ($detect) {
            $version = $this->chromeVersion($os);

            if ($version === false) {
                return false;
            }
        } else {
            $version = $this->argument('version');

            if (!$version) {
                return $this->latestVersion();
            }

            if (!ctype_digit($version)) {
                return $version;
            }

            $version = (int) $version;
        }

        if ($version < 70) {
            return $this->legacyVersion($version);
        } elseif ($version < 115) {
            return $this->fetchChromeVersionFromUrl($version);
        }

        $milestones = $this->resolveChromeVersionsPerMilestone();

        return $milestones['milestones'][$version]['version'] ?? false;
    }

    /**
     * Get the latest stable ChromeDriver version.
     *
     * @return string|false
     */
    protected function latestVersion()
    {
        $versions = json_decode(
            file_get_contents(
                'https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json'
            ),
            true
        );

        return $versions['channels']['Stable']['version'] ?? false;
    }

    /**
     * Get the Chrome version from URL.
     *
     * @param int $version
     * @return string
     */
    protected function fetchChromeVersionFromUrl($version)
    {
        return trim((string) file_get_contents(
            sprintf('https://chromedriver.storage.googleapis.com/LATEST_RELEASE_%d', $version)
        ));
    }

    /**
     * Get the Chrome versions per milestone.
     *
     * @return array
     */
    protected function resolveChromeVersionsPerMilestone()
    {
        return json_decode(
            file_get_contents(
                'https://googlechromelabs.github.io/chrome-for-testing/latest-versions-per-milestone-with-downloads.json'
            ),
            true
        );
    }

    /**
     * Resolve the download URL.
     *
     * @param string $version
     * @param string $os
     * @return string
     */
    protected function resolveChromeDriverDownloadUrl($version, $os)
    {
        $slug = static::chromeDriverSlug($os, $version);

        if (version_compare($version, '115.0', '<')) {
            return sprintf('https://chromedriver.storage.googleapis.com/%s/chromedriver_%s.zip', $version, $slug);
        }

        $milestone = (int) $version;

        $versions = $this->resolveChromeVersionsPerMilestone();

        $chromedrivers = $versions['milestones'][$milestone]['downloads']['chromedriver'];

        return collect($chromedrivers)->firstWhere('platform', $slug)['url'];
    }

    /**
     * Get the ChromeDriver version for a legacy version of Chrome.
     *
     * @param int $version
     * @return string
     */
    protected function legacyVersion($version)
    {
        $legacy = file_get_contents(__DIR__.'/../resources/legacy.json');

        $legacy = json_decode($legacy, true);

        return $legacy[$version];
    }

    /**
     * Check whether the ChromeDriver binary needs to be updated.
     *
     * @param string $os
     * @param string $version
     * @return bool
     */
    protected function checkVersion($os, $version)
    {
        $binary = $this->directory.'chromedriver-'.$os.static::$extensions[$os];

        $process = new Process([$binary, '--version']);

        $process->run();

        preg_match('/[\d.]+/', $process->getOutput(), $matches);

        return isset($matches[0]) ? $matches[0] === $version : false;
    }

    /**
     * Update the ChromeDriver binaries.
     *
     * @param bool $detect
     * @param string $currentOs
     * @param string $version
     * @return void
     */
    protected function update($detect, $currentOs, $version)
    {
        foreach (static::all() as $os) {
            if ($detect && $os !== $currentOs) {
                continue;
            }

            if (version_compare($version, '115.0', '<') && $os === 'mac-arm') {
                continue;
            }

            $archive = $this->download($version, $os);

            $binary = $this->extract($version, $archive);

            $this->rename($binary, $os);
        }
    }

    /**
     * Download the ChromeDriver archive.
     *
     * @param string $version
     * @param string $os
     * @return string
     */
    protected function download($version, $os)
    {
        $archive = $this->directory.'chromedriver.zip';

        $url = $this->resolveChromeDriverDownloadUrl($version, $os);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        file_put_contents($archive, curl_exec($ch));
        curl_close($ch);

        return $archive;
    }

    /**
     * Extract the ChromeDriver binary from the archive and delete the archive.
     *
     * @param string $version
     * @param string $archive
     * @return string
     */
    protected function extract($version, $archive)
    {
        $zip = new ZipArchive;

        $zip->open($archive);

        $zip->extractTo($this->directory);

        $binary = $zip->getNameIndex(version_compare($version, '115.0', '<') ? 0 : 1);

        $zip->close();

        unlink($archive);

        return $binary;
    }

    /**
     * Rename the ChromeDriver binary and make it executable.
     *
     * @param string $binary
     * @param string $os
     * @return void
     */
    protected function rename($binary, $os)
    {
        $binary = str_replace(DIRECTORY_SEPARATOR, '/', $binary);

        $newName = Str::contains($binary, '/')
            ? Str::after(str_replace('chromedriver', 'chromedriver-'.$os, $binary), '/')
            : str_replace('chromedriver', 'chromedriver-'.$os, $binary);

        rename($this->directory.$binary, $this->directory.$newName);

        chmod($this->directory.$newName, 0755);
    }
}
