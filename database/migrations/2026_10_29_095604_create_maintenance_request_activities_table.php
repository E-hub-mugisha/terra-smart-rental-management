
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('maintenance_request_activities')) {
            return;
        }

        Schema::create('maintenance_request_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('maintenance_request_id')
                ->constrained(
                    'maintenance_requests',
                    'id',
                    'mra_request_fk'
                )
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users', 'id', 'mra_user_fk')
                ->nullOnDelete();

            $table->string('activity_type', 40);
            $table->text('description');

            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(
                ['maintenance_request_id', 'created_at'],
                'mra_request_created_idx'
            );

            $table->index('activity_type', 'mra_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_request_activities');
    }
};
