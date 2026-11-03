
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('maintenance_requests')) {
            return;
        }

        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();

            $table->string('request_number', 40)->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id', 'mr_tenant_fk')
                ->restrictOnDelete();

            $table->foreignId('unit_id')
                ->constrained('units', 'id', 'mr_unit_fk')
                ->restrictOnDelete();

            $table->string('title', 180);
            $table->text('description');

            $table->string('category', 40);
            $table->string('priority', 20)->default('normal');
            $table->string('status', 30)->default('submitted');

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users', 'id', 'mr_assignee_fk')
                ->nullOnDelete();

            $table->dateTime('preferred_visit_at')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('resolved_at')->nullable();

            $table->text('resolution_notes')->nullable();

            $table->decimal('estimated_cost', 14, 2)->nullable();
            $table->decimal('actual_cost', 14, 2)->nullable();

            $table->string('attachment_path')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users', 'id', 'mr_creator_fk')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status'], 'mr_tenant_status_idx');
            $table->index(['unit_id', 'status'], 'mr_unit_status_idx');
            $table->index(['priority', 'status'], 'mr_priority_status_idx');
            $table->index(['assigned_to', 'status'], 'mr_assignee_status_idx');
            $table->index('created_at', 'mr_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
