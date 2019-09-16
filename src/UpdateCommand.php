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
     * The URL to the latest stable release version.
     *
     * @var string
     */
    public static $latestVersionUrl = 'https://chromedriver.storage.googleapis.com/LATEST_RELEASE';

    /**
     * The URL to the latest release version of a major Chrome version.
     *
     * @var string
     */
    public static $versionUrl = 'https://chromedriver.storage.googleapis.com/LATEST_RELEASE_%d';

    /**
     * The URL to the ChromeDriver download.
     *
     * @var string
     */
    public static $downloadUrl = 'https://chromedriver.storage.googleapis.com/%s/chromedriver_%s.zip';

    /**
     * The download slugs for the available operating systems.
     *
     * @var array
     */
    public static $slugs = [
        'linux' => 'linux64',
        'mac' => 'mac64',
        'win' => 'win32',
    ];

    /**
     * The file extensions of the ChromeDriver binaries.
     *
     * @var array
     */
    public static $extensions = [
        'linux' => '',
        'mac' => '',
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
        return trim(file_get_contents(static::$latestVersionUrl));
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
        foreach (static::$slugs as $os => $slug) {
            if ($detect && $os !== $currentOs) {
                continue;
            }

            $archive = $this->download($version, $slug);

            $binary = $this->extract($archive);

            $this->rename($binary, $os);
        }
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
