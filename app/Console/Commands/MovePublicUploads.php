<?php

namespace App\Console\Commands;

use App\Support\FileUpload;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * Moves images that were saved in <project>/public/image into the real web
 * root (e.g. public_html/image) after APP_PUBLIC_PATH has been set. Database
 * paths are relative ("image/properties/..."), so they stay valid.
 */
class MovePublicUploads extends Command
{
    protected $signature = 'uploads:move-public
        {--from= : Folder that currently holds image/ (default: the project public/ folder)}
        {--dry-run : List what would move without moving anything}';

    protected $description = 'Move uploaded images from the project public/ folder into the configured web root (e.g. public_html).';

    public function handle(): int
    {
        $from = rtrim($this->option('from') ?: base_path('public'), '\\/');
        $to = FileUpload::root(false);

        if (realpath($from) && realpath($from) === realpath($to)) {
            $this->info("Images are already in the web root ({$to}). Nothing to move.");

            return self::SUCCESS;
        }

        if (! is_dir($from . DIRECTORY_SEPARATOR . 'image')) {
            $this->info("No image/ folder found in {$from}. Nothing to move.");

            return self::SUCCESS;
        }

        $this->line("From: {$from}" . DIRECTORY_SEPARATOR . 'image');
        $this->line("To:   {$to}" . DIRECTORY_SEPARATOR . 'image');

        $moved = $skipped = 0;
        foreach (Finder::create()->files()->in($from . DIRECTORY_SEPARATOR . 'image') as $file) {
            $relative = 'image/' . str_replace('\\', '/', $file->getRelativePathname());
            $destination = FileUpload::absolutePath($relative);

            if (is_file($destination)) {
                $skipped++;
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  {$relative}");
                $moved++;
                continue;
            }

            if (! is_dir(dirname($destination)) && ! mkdir(dirname($destination), (int) config('uploads.directory_mode', 0755), true) && ! is_dir(dirname($destination))) {
                $this->error('Cannot create ' . dirname($destination) . ' — check folder permissions.');

                return self::FAILURE;
            }

            if (! @rename($file->getPathname(), $destination)) {
                if (! @copy($file->getPathname(), $destination)) {
                    $this->warn("  Could not move {$relative}");
                    continue;
                }
                @unlink($file->getPathname());
            }
            $moved++;
        }

        $this->info(($this->option('dry-run') ? '[dry run] ' : '') . "Moved {$moved} file(s); {$skipped} already existed in the web root.");

        return self::SUCCESS;
    }
}
