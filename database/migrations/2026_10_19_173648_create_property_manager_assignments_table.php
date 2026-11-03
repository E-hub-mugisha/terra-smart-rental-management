<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_manager_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained('properties')
                ->restrictOnDelete();

            $table->foreignId('property_manager_id')
                ->constrained('property_managers')
                ->restrictOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['property_manager_id', 'status']);
            $table->index(['property_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_manager_assignments');
    }
};