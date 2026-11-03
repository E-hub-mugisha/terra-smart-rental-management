<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();

            $table->foreignId('building_id')
                ->constrained('buildings')
                ->restrictOnDelete();

            $table->foreignId('floor_id')
                ->nullable()
                ->constrained('floors')
                ->nullOnDelete();

            $table->string('unit_number', 50);
            $table->string('unit_type', 50)->index();
            $table->unsignedTinyInteger('bedrooms')->default(0);
            $table->unsignedTinyInteger('bathrooms')->default(0);
            $table->decimal('area_sqm', 10, 2)->nullable();
            $table->decimal('rent_amount', 14, 2);
            $table->decimal('deposit_amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('RWF');
            $table->string('status', 30)->default('available')->index();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['building_id', 'unit_number']);
            $table->index(['building_id', 'status']);
            $table->index(['floor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};