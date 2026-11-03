<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_owner_id')
                ->constrained('property_owners')
                ->restrictOnDelete();

            $table->string('property_code', 40)->unique();
            $table->string('name');
            $table->string('property_type', 50)->index();
            $table->text('description')->nullable();

            $table->string('country', 100)->default('Rwanda');
            $table->string('province', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('sector', 100)->nullable();
            $table->string('cell', 100)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('address')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->unsignedInteger('year_built')->nullable();
            $table->unsignedInteger('total_buildings')->default(0);
            $table->unsignedInteger('total_units')->default(0);

            $table->string('status', 30)->default('active')->index();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['property_owner_id', 'status']);
            $table->index(['district', 'sector']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};