<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_managers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('manager_number', 30)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('job_title')->nullable();
            $table->string('employment_type', 30)->default('full_time');
            $table->date('start_date')->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'employment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_managers');
    }
};
