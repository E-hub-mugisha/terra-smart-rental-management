<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 2 of the Terra brief:
 *  - lease_templates / lease_contracts   contract builder with configurable clauses (§8)
 *  - service_providers + maintenance cols service provider network, technician jobs,
 *                                        tenant confirmation (§4E, §14, §15)
 *  - property_user_roles                 accountants and agents scoped to properties (§4D, §4F)
 *  - agent_commissions                   agent commissions (§4D)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lease_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('clauses');           // [{title, body}] with {{placeholders}}
            $table->boolean('is_default')->default(false);
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete(); // null = available everywhere
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lease_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('lease_template_id')->nullable()->constrained()->nullOnDelete();
            $table->json('clauses');           // rendered clauses, editable while draft
            $table->json('variables');         // values used when generating
            $table->string('status', 20)->default('draft'); // draft | final
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });

        Schema::create('service_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->json('categories');
            $table->string('district', 100)->nullable();
            $table->string('status', 20)->default('active')->index(); // active | suspended
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->foreignId('service_provider_id')->nullable()->after('assigned_to')->constrained()->nullOnDelete();
            $table->timestamp('tenant_confirmed_at')->nullable()->after('resolution_notes');
            $table->unsignedTinyInteger('tenant_rating')->nullable()->after('tenant_confirmed_at');
            $table->text('tenant_feedback')->nullable()->after('tenant_rating');
        });

        Schema::create('property_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20); // accountant | agent
            $table->decimal('commission_rate', 5, 2)->nullable(); // agents: % of first month's rent
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['property_id', 'user_id', 'role']);
        });

        Schema::create('agent_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lease_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('RWF');
            $table->string('status', 20)->default('pending')->index(); // pending | approved | paid | cancelled
            $table->string('description')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // A starting template. Terra should have it reviewed against current Rwandan law before use.
        DB::table('lease_templates')->insert([
            'name' => 'Standard residential lease',
            'description' => 'General residential tenancy agreement. Review with a legal adviser and adapt to each property before use.',
            'is_default' => true,
            'clauses' => json_encode([
                ['title' => 'Parties', 'body' => "This agreement is made between {{landlord_name}} (the Landlord) and {{tenant_name}}, holder of {{tenant_id_type}} number {{tenant_id_number}} (the Tenant)."],
                ['title' => 'Premises', 'body' => "The Landlord lets to the Tenant Unit {{unit_number}} at {{property_name}}, {{property_address}} (the Premises), for residential use by the Tenant and up to {{occupants}} occupant(s)."],
                ['title' => 'Term', 'body' => "The tenancy starts on {{start_date}} and ends on {{end_date}}, unless renewed or ended earlier under this agreement."],
                ['title' => 'Rent', 'body' => "The rent is {{monthly_rent}} per month, payable in advance on or before day {{rent_due_day}} of each month through the Terra platform or another channel agreed in writing."],
                ['title' => 'Security deposit', 'body' => "The Tenant pays a security deposit of {{deposit_amount}}. It is returned at the end of the tenancy after the move-out inspection, less any amounts owed for unpaid rent or damage beyond normal wear and tear, as recorded against the move-in inspection."],
                ['title' => 'Utilities', 'body' => "Unless agreed otherwise in writing, the Tenant pays for electricity, water and other utilities used at the Premises from the start date. Meter readings are recorded at the move-in and move-out inspections."],
                ['title' => 'Maintenance and repairs', 'body' => "The Tenant keeps the Premises clean and reports defects promptly through Terra. The Landlord carries out structural repairs and repairs to fixtures and installations not caused by the Tenant's misuse."],
                ['title' => 'House rules', 'body' => "The Tenant respects the house rules of {{property_name}}, keeps noise reasonable, and does not sublet or make alterations without the Landlord's written consent."],
                ['title' => 'Notice and termination', 'body' => "Either party may end this agreement by giving at least {{notice_period_days}} days' written notice. The Landlord may end the agreement early for serious breach, including non-payment of rent, as permitted by law."],
                ['title' => 'Renewal', 'body' => "The tenancy may be renewed on terms agreed by both parties. Renewal offers and acceptances made through Terra are recorded against this lease."],
                ['title' => 'Special conditions', 'body' => "{{special_conditions}}"],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commissions');
        Schema::dropIfExists('property_user_roles');
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_provider_id');
            $table->dropColumn(['tenant_confirmed_at', 'tenant_rating', 'tenant_feedback']);
        });
        Schema::dropIfExists('service_providers');
        Schema::dropIfExists('lease_contracts');
        Schema::dropIfExists('lease_templates');
    }
};
