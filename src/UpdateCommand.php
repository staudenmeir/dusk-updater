<?php

namespace Staudenmeir\DuskUpdater;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ZipArchive;

class UpdateCommand extends Command
{
    use DetectsChromeVersion;

    /**
     * URL to the home page.
     *
     * @var string
     */
    public static $homeUrl = 'http://chromedriver.chromium.org/home';

    /**
     * URL to the latest release version.
     *
     * @var string
     */
    public static $versionUrl = 'https://chromedriver.storage.googleapis.com/LATEST_RELEASE_%d';

    /**
     * URL to the ChromeDriver download.
     *
     * @var string
     */
    public static $downloadUrl = 'https://chromedriver.storage.googleapis.com/%s/chromedriver_%s.zip';

    /**
     * Download slugs for the available operating systems.
     *
     * @var array
     */
    public static $slugs = [
        'linux' => 'linux64',
        'mac' => 'mac64',
        'win' => 'win32',
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
     * Path to the bin directory.
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

        $currentOs = $this->os();

        $version = $this->version($detect, $currentOs);

        if ($version === false) {
            return 1;
        }

        foreach (static::$slugs as $os => $slug) {
            if ($detect && $os !== $currentOs) {
                continue;
            }

            $archive = $this->download($version, $slug);

            $binary = $this->extract($archive);

            $this->rename($binary, $os);
        }

        $this->info(
            sprintf('ChromeDriver %s successfully updated to version %s.', $detect ? 'binary' : 'binaries', $version)
        );

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
        }

        $url = sprintf(static::$versionUrl, $version);

        return trim(file_get_contents($url));
    }

    /**
     * Get the latest stable ChromeDriver version.
     *
     * @return string
     */
    protected function latestVersion()
    {
        $home = file_get_contents(static::$homeUrl);

        preg_match('/Latest stable release:.*?\?path=([\d.]+)/', $home, $matches);

        return $matches[1];
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
     * Download the ChromeDriver archive.
     *
     * @param string $version
     * @param string $slug
     * @return string
     */
    protected function download($version, $slug)
    {
        $archive = $this->directory.'chromedriver.zip';

        $url = sprintf(static::$downloadUrl, $version, $slug);

        file_put_contents($archive, fopen($url, 'r'));

        return $archive;
    }

    /**
     * Extract the ChromeDriver binary from the archive and delete the archive.
     *
     * @param string $archive
     * @return string
     */
    protected function extract($archive)
    {
        $zip = new ZipArchive;

        $zip->open($archive);

        $zip->extractTo($this->directory);

        $binary = $zip->getNameIndex(0);

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
        $newName = str_replace('chromedriver', 'chromedriver-'.$os, $binary);

        rename($this->directory.$binary, $this->directory.$newName);

        chmod($this->directory.$newName, 0755);
    }

    /**
     * Detect the current operating system.
     *
     * @return string
     */
    protected function os()
    {
        return PHP_OS === 'WINNT' || Str::contains(php_uname(), 'Microsoft')
            ? 'win'
            : (PHP_OS === 'Darwin' ? 'mac' : 'linux');
    }
}
