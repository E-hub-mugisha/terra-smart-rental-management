<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets an employee be a platform user (property manager, accountant, agent,
 * field staff): their HR record links to the login account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('property_id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'property_id']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
