<?php

use App\Support\FileUpload;

if (! function_exists('upload_url')) {
    /** Public URL for an uploaded public file path saved in the database. */
    function upload_url(?string $path): ?string
    {
        return FileUpload::url($path);
    }
}
