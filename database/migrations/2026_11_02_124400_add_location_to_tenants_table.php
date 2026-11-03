<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->default('Rwanda')->after('alternative_phone');
            $table->string('province', 100)->nullable()->after('country');
            $table->string('district', 100)->nullable()->after('province');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['country', 'province', 'district']);
        });
    }
};
