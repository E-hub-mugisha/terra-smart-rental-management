<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Move-in & move-out inspections (brief §16) and deposit settlement (§3).
 *
 * An inspection belongs to a lease. Each one is a checklist of items
 * (walls, doors, windows, furniture, appliances, inventory…) grouped by
 * room, with condition, notes and photos/videos, plus meter readings.
 * The move-out inspection points at the move-in one so the two can be
 * compared item by item, and carries the deposit settlement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_number', 40)->unique();
            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->index();                 // move_in | move_out
            $table->string('status', 30)->default('draft')->index(); // draft | awaiting_tenant | disputed | completed
            $table->foreignId('baseline_inspection_id')->nullable()->constrained('inspections')->nullOnDelete();
            $table->date('inspection_date');
            $table->foreignId('conducted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('electricity_reading', 12, 2)->nullable();
            $table->decimal('water_reading', 12, 2)->nullable();
            $table->unsignedSmallInteger('keys_handed')->nullable();
            $table->text('general_notes')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('tenant_responded_at')->nullable();
            $table->text('tenant_comment')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Move-out deposit settlement. Terra records the outcome only; it does not move funds.
            $table->decimal('deposit_held', 14, 2)->nullable();
            $table->decimal('deposit_deductions', 14, 2)->nullable();
            $table->decimal('deposit_refund', 14, 2)->nullable();
            $table->string('currency', 3)->default('RWF');
            $table->timestamp('deposit_settled_at')->nullable();
            $table->string('deposit_refund_reference')->nullable();
            $table->text('settlement_notes')->nullable();

            $table->timestamps();

            $table->index(['lease_id', 'type']);
        });

        Schema::create('inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('baseline_item_id')->nullable()->constrained('inspection_items')->nullOnDelete();
            $table->string('area', 80);      // Living room, Kitchen, Bedroom 1, Inventory…
            $table->string('item', 120);     // Walls, Doors, Windows, Fridge…
            $table->string('condition', 20)->nullable(); // excellent|good|fair|poor|damaged|missing|na
            $table->unsignedSmallInteger('quantity')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('deduction_amount', 14, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('inspection_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('caption')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_media');
        Schema::dropIfExists('inspection_items');
        Schema::dropIfExists('inspections');
    }
};
