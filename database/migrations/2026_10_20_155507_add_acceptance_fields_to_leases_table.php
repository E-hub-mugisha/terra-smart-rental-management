<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->timestamp('tenant_accepted_at')->nullable();
            $table->timestamp('move_in_confirmed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn([
                'tenant_accepted_at',
                'move_in_confirmed_at',
                'activated_at',
            ]);
        });
    }
};