<?php

/*
|--------------------------------------------------------------------------
| File uploads (custom local storage with PHP move())
|--------------------------------------------------------------------------
|
| Every upload is moved straight into a folder inside the project.
|
|  - Public files (property, building, floor and unit photos, maintenance
|    photos) live under public/ and are served directly by the web server,
|    e.g. public/image/properties/1731937200_6744a1b2c3d4e.jpg.
|
|  - Private files (contracts, ID documents, inspection evidence, message
|    attachments) live under the project's uploads/ folder, OUTSIDE public/,
|    and are only streamed through controllers that check permissions.
|
| Only the relative path (e.g. "image/properties/1731937200_6744a1b2c3d4e.jpg")
| is saved in the database.
*/

return [

    'public_root' => env('UPLOADS_PUBLIC_ROOT') ?: public_path(),

    'private_root' => env('UPLOADS_PRIVATE_ROOT') ?: base_path('uploads'),

    // Folder permissions used when a destination folder is created.
    'directory_mode' => 0755,

];
