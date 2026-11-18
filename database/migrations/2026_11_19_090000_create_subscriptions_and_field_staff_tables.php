<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Terra admin subscriber management (plans, subscription contracts,
 * invoices, payments, call log), the field staff role, and a phone
 * number on user accounts (E.164).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 20)->nullable()->after('email');
            });
        }

        Schema::create('field_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('staff_number', 30)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('status', 20)->default('active'); // active, suspended
            $table->text('notes')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 40)->unique();
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, quarterly, annual
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 3)->default('RWF');
            $table->unsignedInteger('max_properties')->nullable();
            $table->unsignedInteger('max_units')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscription_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 30)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the subscriber (owner or manager)
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 150);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('billing_cycle', 20)->default('monthly');
            $table->decimal('amount', 12, 2)->default(0); // per billing cycle
            $table->string('currency', 3)->default('RWF');
            $table->unsignedSmallInteger('payment_due_days')->default(7);
            $table->string('status', 20)->default('pending'); // pending, active, expired, terminated
            $table->boolean('auto_renew')->default(false);
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->string('termination_reason', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'end_date']);
        });

        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->unique();
            $table->foreignId('subscription_contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('currency', 3)->default('RWF');
            $table->string('status', 20)->default('unpaid'); // unpaid, partial, paid, void
            $table->string('notes', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'due_date']);
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 60)->nullable();
            $table->foreignId('subscription_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('RWF');
            $table->string('method', 30); // mobile_money, bank_transfer, cash, card
            $table->timestamp('paid_at');
            $table->string('notes', 255)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('subscriber_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // subscriber
            $table->foreignId('called_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('direction', 10)->default('outbound'); // outbound, inbound
            $table->string('channel', 20)->default('phone'); // phone, whatsapp, browser, email
            $table->string('phone', 20)->nullable();
            $table->string('room', 80)->nullable(); // in-browser call room
            $table->string('outcome', 20); // answered, no_answer, busy, voicemail, callback, sent, started
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('subject', 150)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('called_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriber_calls');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('subscription_contracts');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('field_staff');

        if (Schema::hasColumn('users', 'phone')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('phone'));
        }
    }
};
