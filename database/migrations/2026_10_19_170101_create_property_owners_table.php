<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_owners', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('owner_number', 30)->unique();
            $table->string('owner_type', 30)->default('individual');
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('national_id', 100)->nullable();
            $table->string('company_name')->nullable();
            $table->string('country', 100)->default('Rwanda');
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('address')->nullable();

            $table->string('verification_status', 30)->default('pending');
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'verification_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_owners');
    }
};
