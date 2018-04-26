<?php

namespace Staudenmeir\DuskUpdater;

use Illuminate\Console\Command;
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
        $version = $this->argument('version') ?
            $this->argument('version') :
            trim(file_get_contents(static::$versionUrl));

        foreach (static::$slugs as $os => $slug) {
            $archive = $this->download($version, $slug);

            $binary = $this->extract($archive);

            $this->rename($binary, $os);
        }

        $this->info('ChromeDriver binaries updated successfully.');
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
}
