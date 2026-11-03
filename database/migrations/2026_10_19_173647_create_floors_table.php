<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('floors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('building_id')
                ->constrained('buildings')
                ->restrictOnDelete();

            $table->string('name');
            $table->integer('floor_number');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['building_id', 'floor_number']);
            $table->index(['building_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floors');
    }
};