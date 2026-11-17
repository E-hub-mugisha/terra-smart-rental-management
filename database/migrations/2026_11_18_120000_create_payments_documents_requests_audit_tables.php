<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remaining MVP pieces from the Terra brief:
 *  - payment_intents   online rent payment through a provider (§9)
 *  - documents         document management + tenant KYC documents (§6, MVP #14)
 *  - tenants KYC cols  ID type/number, occupants, verification status (§6)
 *  - lease_requests    lease renewal and tenant move-out notice (§4B)
 *  - audit_logs        who changed what, when (§24)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rent_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30);
            $table->string('phone', 30)->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->string('status', 20)->default('pending')->index(); // pending | successful | failed | expired
            $table->string('provider_reference')->nullable()->index();
            $table->string('failure_reason')->nullable();
            $table->foreignId('rent_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->json('provider_payload')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable'); // Property, Unit, Tenant, Lease
            $table->string('category', 40)->index();
            $table->string('title');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending')->index(); // pending | verified | rejected | not_required
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('id_type', 20)->nullable()->after('alternative_phone'); // national_id | passport | refugee_id
            $table->string('id_number', 40)->nullable()->after('id_type');
            $table->unsignedTinyInteger('occupants')->nullable()->after('id_number');
            $table->string('kyc_status', 20)->default('unverified')->after('occupants')->index(); // unverified | pending | verified | rejected
            $table->timestamp('kyc_verified_at')->nullable()->after('kyc_status');
        });

        Schema::create('lease_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->index();   // renewal | move_out
            $table->string('status', 20)->default('requested')->index();
            // renewal: requested → offered → accepted | declined ; staff may also decline a request or start with an offer
            // move_out: requested → acknowledged | withdrawn
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('requested_months')->nullable();
            $table->date('move_out_date')->nullable();
            $table->text('message')->nullable();

            $table->date('current_end_date')->nullable();
            $table->decimal('current_rent', 14, 2)->nullable();
            $table->date('offered_end_date')->nullable();
            $table->decimal('offered_rent', 14, 2)->nullable();
            $table->text('response')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 40)->index();
            $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('lease_requests');
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['kyc_status']);
            $table->dropColumn(['id_type', 'id_number', 'occupants', 'kyc_status', 'kyc_verified_at']);
        });
        Schema::dropIfExists('documents');
        Schema::dropIfExists('payment_intents');
    }
};
