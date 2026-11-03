<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rent_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rent_payment_id')->constrained('rent_payments')->onDelete('cascade');
            $table->foreignId('rent_invoice_id')->constrained('rent_invoices')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('allocated_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['rent_payment_id', 'rent_invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_payment_allocations');
    }
};
