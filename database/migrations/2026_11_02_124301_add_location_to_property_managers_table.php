<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_managers', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->default('Rwanda')->after('phone');
            $table->string('province', 100)->nullable()->after('country');
            $table->string('district', 100)->nullable()->after('province');
            $table->string('address', 255)->nullable()->after('district');
        });
    }

    public function down(): void
    {
        Schema::table('property_managers', function (Blueprint $table) {
            $table->dropColumn(['country', 'province', 'district', 'address']);
        });
    }
};