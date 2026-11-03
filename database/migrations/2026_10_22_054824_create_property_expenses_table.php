<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_expenses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained('properties')
                ->restrictOnDelete();

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('expense_number', 50)->unique();
            $table->string('category', 100)->index();
            $table->string('title');
            $table->text('description')->nullable();

            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('RWF');
            $table->date('expense_date');
            $table->string('payment_method', 50)->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('reference_number')->nullable();

            $table->string('status', 30)->default('paid')->index();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['property_id', 'expense_date']);
            $table->index(['currency', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_expenses');
    }
};