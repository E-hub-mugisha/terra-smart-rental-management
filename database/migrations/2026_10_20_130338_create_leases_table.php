
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();
            $table->foreignId('tenant_application_id')
                ->nullable()
                ->unique()
                ->constrained('tenant_applications')
                ->nullOnDelete();
            $table->string('lease_number', 50)->unique();

            $table->date('start_date');
            $table->date('end_date');

            $table->decimal('monthly_rent', 14, 2);
            $table->decimal('deposit_amount', 14, 2)->default(0);

            $table->string('currency', 3)->default('RWF');

            $table->unsignedTinyInteger('rent_due_day')->default(1);

            $table->string('payment_frequency', 30)
                ->default('monthly');

            $table->string('status', 30)
                ->default('draft')
                ->index();

            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();

            $table->text('terms')->nullable();
            $table->string('document_path')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['unit_id', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
