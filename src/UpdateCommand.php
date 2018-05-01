<?php

namespace Staudenmeir\DuskUpdater;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use ZipArchive;

class UpdateCommand extends Command
{
    /**
     * URL to the latest release version.
     *
     * @var string
     */
    public static $versionUrl = 'https://chromedriver.storage.googleapis.com/LATEST_RELEASE';

    /**
     * URL to the ChromeDriver download.
     *
     * @var string
     */
    public static $downloadUrl = 'https://chromedriver.storage.googleapis.com/%s/chromedriver_%s.zip';

    /**
     * URL to the ChromeDriver notes.
     *
     * @var string
     */
    public static $notesUrl = 'https://chromedriver.storage.googleapis.com/%s/notes.txt';

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
    protected $signature = 'dusk:update {version?}';

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
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $latest = trim(file_get_contents(static::$versionUrl));

        $versions = $this->versions($latest);

        $version = $this->version($versions, $latest);

        foreach (static::$slugs as $os => $slug) {
            $archive = $this->download($version, $slug);

            $binary = $this->extract($archive);

            $this->rename($binary, $os);
        }

        $message = "ChromeDriver binaries successfully updated to version %s (Chrome v%d-%d).";

        $this->info(sprintf($message, $version, $versions[$version]['min'], $versions[$version]['max']));
    }

    /**
     * Download the ChromeDriver archive.
     *
     * @param  string  $version
     * @param  string  $slug
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
     * @param  string  $archive
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
     * @param  string  $binary
     * @param  string  $os
     * @return void
     */
    protected function rename($binary, $os)
    {
        $newName = str_replace('chromedriver', 'chromedriver-'.$os, $binary);

        rename($this->directory.$binary, $this->directory.$newName);

        chmod($this->directory.$newName, 0755);
    }

    /**
     * Get the desired ChromeDriver version.
     *
     * @param  Collection  $versions
     * @param  string  $latest
     * @return string
     */
    protected function version(Collection $versions, $latest)
    {
        $version = $this->argument('version');

        if ($version && !ctype_digit($version)) {
            return $version;
        }

        $version = $versions->where('min', '<=', $version)->keys()->first();

        return $version ? $version : $latest;
    }

    /**
     * Get the available ChromeDriver versions.
     *
     * @param  string  $latest
     * @return Collection
     */
    protected function versions($latest)
    {
        $versions = collect();

        $notes = file_get_contents(sprintf(static::$notesUrl, $latest));

        preg_match_all('#ChromeDriver v(\S+).*?\nSupports Chrome v(\d+)-(\d+)#', $notes, $matches, PREG_SET_ORDER);

        foreach($matches as $match) {
            $versions[$match[1]] = [
                'min' => (int) $match[2],
                'max' => (int) $match[3]
            ];
        }

        return $versions;
    }
}
