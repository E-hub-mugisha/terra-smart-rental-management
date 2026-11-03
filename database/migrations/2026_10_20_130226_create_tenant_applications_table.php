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
        Schema::create('tenant_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->restrictOnDelete();
            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('application_number')->unique();
            $table->date('application_date');
            $table->date('desired_move_in_date')->nullable();
            $table->integer('number_of_occupants')->default(1);
            $table->decimal('offered_rent', 10, 2)->nullable();
            $table->string('currency', 3)->default('RWF');
            $table->string('employment_status', 50)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('decision_notes')->nullable();
            
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'unit_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_applications');
    }
};
