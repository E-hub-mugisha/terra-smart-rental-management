<?php

namespace App\Support;

use App\Exceptions\UploadFailedException;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Custom local file storage using PHP move().
 *
 * Files are moved into folders inside the project and only the relative path
 * is stored in the database. Public images live under public/, private files
 * (contracts, IDs, inspection evidence, message attachments) under uploads/,
 * which the web server never serves directly.
 */
final class FileUpload
{
    /* ---- Public folders (served directly) ---- */
    public const PROPERTY_IMAGES = 'image/properties';
    public const BUILDING_IMAGES = 'image/buildings';
    public const FLOOR_IMAGES = 'image/floors';
    public const UNIT_IMAGES = 'image/units';
    public const MAINTENANCE_IMAGES = 'image/maintenance';

    /* ---- Private folders (streamed through controllers) ---- */
    public const CONTRACTS = 'documents/contracts';
    public const KYC = 'documents/kyc';
    public const PROPERTY_DOCUMENTS = 'documents/properties';
    public const GENERAL_DOCUMENTS = 'documents/general';
    public const INSPECTIONS = 'inspections';
    public const MESSAGES = 'messages';

    /** Extensions that must never be written to disk, whatever the client claims. */
    private const BLOCKED_EXTENSIONS = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'pht', 'phps', 'cgi', 'pl', 'py', 'sh', 'exe', 'bat', 'cmd', 'js', 'html', 'htm', 'svg', 'htaccess', 'asp', 'aspx', 'jsp'];

    /**
     * Moves an uploaded file into $directory and returns the relative path to save,
     * e.g. "image/properties/1731937200_6744a1b2c3d4e.jpg".
     *
     * @throws UploadFailedException when the file is invalid or cannot be written
     */
    public static function store(UploadedFile $file, string $directory, bool $private = false): string
    {
        if (! $file->isValid()) {
            throw new UploadFailedException('The file "' . $file->getClientOriginalName() . '" failed to upload: ' . $file->getErrorMessage());
        }

        $directory = self::cleanDirectory($directory);
        $destinationPath = self::root($private) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $directory);

        if (! is_dir($destinationPath) && ! @mkdir($destinationPath, (int) config('uploads.directory_mode', 0755), true) && ! is_dir($destinationPath)) {
            throw new UploadFailedException('The upload folder "' . $directory . '" could not be created. Check folder permissions.');
        }

        $filename = time() . '_' . uniqid() . '.' . self::extensionFor($file);

        try {
            $file->move($destinationPath, $filename);
        } catch (FileException $e) {
            throw new UploadFailedException('The file "' . $file->getClientOriginalName() . '" could not be saved. Please try again.', 0, $e);
        }

        return $directory . '/' . $filename;
    }

    /**
     * Stores several files. If one fails, the ones already moved are removed again.
     *
     * @param  UploadedFile[]  $files
     * @return string[] relative paths, in the same order
     */
    public static function storeMany(array $files, string $directory, bool $private = false): array
    {
        $paths = [];

        try {
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $paths[] = self::store($file, $directory, $private);
                }
            }
        } catch (\Throwable $e) {
            self::deleteMany($paths, $private);

            throw $e;
        }

        return $paths;
    }

    /** Deletes a stored file. Missing files are ignored. */
    public static function delete(?string $path, bool $private = false): bool
    {
        $absolute = self::absolutePath($path, $private);

        return $absolute !== null && is_file($absolute) && @unlink($absolute);
    }

    /** @param  array<int, string|null>  $paths */
    public static function deleteMany(array $paths, bool $private = false): void
    {
        foreach ($paths as $path) {
            self::delete($path, $private);
        }
    }

    public static function exists(?string $path, bool $private = false): bool
    {
        $absolute = self::absolutePath($path, $private);

        return $absolute !== null && is_file($absolute);
    }

    public static function size(?string $path, bool $private = false): int
    {
        return self::exists($path, $private) ? (int) filesize(self::absolutePath($path, $private)) : 0;
    }

    /**
     * Writes generated content (used by the demo seeder) and returns the relative path.
     */
    public static function put(string $directory, string $filename, string $contents, bool $private = false): string
    {
        $directory = self::cleanDirectory($directory);
        $filename = basename($filename);
        $destinationPath = self::root($private) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $directory);

        if (! is_dir($destinationPath) && ! @mkdir($destinationPath, (int) config('uploads.directory_mode', 0755), true) && ! is_dir($destinationPath)) {
            throw new UploadFailedException('The folder "' . $directory . '" could not be created.');
        }

        if (file_put_contents($destinationPath . DIRECTORY_SEPARATOR . $filename, $contents) === false) {
            throw new UploadFailedException('The file "' . $filename . '" could not be written.');
        }

        return $directory . '/' . $filename;
    }

    /**
     * Absolute path of a stored file, or null when the path is empty or tries
     * to leave the upload root (e.g. "../../.env").
     */
    public static function absolutePath(?string $path, bool $private = false): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', $path), '/');
        if (str_contains('/' . $relative . '/', '/../') || str_contains($relative, "\0")) {
            return null;
        }

        return self::root($private) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    /** Public URL of a public file (falls back to the old storage link for files not yet migrated). */
    public static function url(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (! self::exists($path) && is_file(public_path('storage/' . ltrim($path, '/')))) {
            return asset('storage/' . ltrim($path, '/'));
        }

        return asset(ltrim(str_replace('\\', '/', $path), '/'));
    }

    /**
     * Streams a private file to an authorised user, inline (PDFs, images, video)
     * or as a download.
     */
    public static function response(string $path, string $downloadName, ?string $mimeType = null, bool $inline = true, array $headers = []): BinaryFileResponse
    {
        $absolute = self::absolutePath($path, true);
        abort_unless($absolute !== null && is_file($absolute), 404);

        $response = new BinaryFileResponse($absolute, 200, $headers + [
            'Content-Type' => $mimeType ?: (mime_content_type($absolute) ?: 'application/octet-stream'),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);

        $fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName) ?: 'file';
        $response->setContentDisposition($inline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT, $downloadName, $fallback);

        return $response;
    }

    public static function root(bool $private = false): string
    {
        $root = $private ? config('uploads.private_root') : config('uploads.public_root');

        return rtrim($root ?: ($private ? base_path('uploads') : public_path()), '\\/');
    }

    private static function cleanDirectory(string $directory): string
    {
        $directory = trim(str_replace('\\', '/', $directory), '/');
        if ($directory === '' || str_contains('/' . $directory . '/', '/../') || ! preg_match('#^[A-Za-z0-9/_-]+$#', $directory)) {
            throw new UploadFailedException('Invalid upload folder "' . $directory . '".');
        }

        return $directory;
    }

    private static function extensionFor(UploadedFile $file): string
    {
        $extension = strtolower(preg_replace('/[^A-Za-z0-9]/', '', (string) $file->getClientOriginalExtension()));

        if ($extension === '' || in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            $extension = strtolower((string) $file->guessExtension());
        }

        return ($extension === '' || in_array($extension, self::BLOCKED_EXTENSIONS, true)) ? 'bin' : $extension;
    }
}
