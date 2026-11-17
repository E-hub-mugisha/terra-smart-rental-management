<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aligns the property and unit digital profiles with section 5 of the
 * Terra Property Management System brief (UPI, amenities, utilities,
 * media, unit features, utility meters and inventory).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('upi', 60)->nullable()->after('property_code')->index();
            $table->json('amenities')->nullable()->after('description');
            $table->json('utilities')->nullable()->after('amenities');
            $table->string('video_url')->nullable()->after('utilities');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->json('features')->nullable()->after('description');
            $table->string('electricity_meter', 60)->nullable()->after('features');
            $table->string('water_meter', 60)->nullable()->after('electricity_meter');
            $table->text('inventory')->nullable()->after('water_meter');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['features', 'electricity_meter', 'water_meter', 'inventory']);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['upi']);
            $table->dropColumn(['upi', 'amenities', 'utilities', 'video_url']);
        });
    }
};
