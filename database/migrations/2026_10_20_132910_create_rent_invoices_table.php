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
        Schema::create('rent_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')
                ->constrained('leases')
                ->restrictOnDelete();
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->restrictOnDelete();
            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();
            $table->string('invoice_type', 20)->default('rent');
            $table->string('invoice_number')->unique();
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('balance_due', 10, 2);
            $table->text('description')->nullable();
            $table->string('currency', 3)->default('RWF');
            $table->string('status', 20)->default('unpaid');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['lease_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_invoices');
    }
};
