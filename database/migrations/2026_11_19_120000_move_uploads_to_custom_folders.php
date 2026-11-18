<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Moves files uploaded by the old Storage-based code into the new custom
 * folders (public/image/... and uploads/...) and updates their paths.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('uploads:migrate-legacy');
    }

    public function down(): void
    {
        // Files stay in their new folders; the paths remain valid.
    }
};
