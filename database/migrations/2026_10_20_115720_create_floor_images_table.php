<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('floor_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('floor_id')
                ->constrained('floors')
                ->cascadeOnDelete();

            $table->string('image_path');
            $table->string('caption')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['floor_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floor_images');
    }
};