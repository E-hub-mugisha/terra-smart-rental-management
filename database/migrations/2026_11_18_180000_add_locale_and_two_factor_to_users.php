<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user language (brief §23) and optional two-step sign-in with an
 * emailed one-time code (brief §24 "multi-factor authentication where appropriate").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->default('en')->after('notify_sms');
            $table->boolean('two_factor_enabled')->default(false)->after('locale');
            $table->string('two_factor_code')->nullable()->after('two_factor_enabled');
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code');
            $table->unsignedTinyInteger('two_factor_attempts')->default(0)->after('two_factor_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'two_factor_enabled', 'two_factor_code', 'two_factor_expires_at', 'two_factor_attempts']);
        });
    }
};
