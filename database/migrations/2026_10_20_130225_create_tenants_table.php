
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            // Optional link to the tenant's login account.
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('tenant_number', 50)->unique();

            $table->string('full_name', 150);
            $table->string('email')->nullable()->index();
            $table->string('phone', 30);
            $table->string('alternative_phone', 30)->nullable();

            $table->text('address')->nullable();

            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();

            $table->string('status', 30)->default('active')->index();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
