<?php

use App\Support\Phone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rewrites stored phone numbers to E.164 (+250788123456). Numbers that are
 * not valid for any country are left exactly as they were, so nothing is lost.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'users' => ['phone'],
        'property_owners' => ['phone'],
        'property_managers' => ['phone'],
        'tenants' => ['phone', 'alternative_phone', 'emergency_contact_phone'],
        'service_providers' => ['phone'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $columns = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($table, $c)));
            if (! $columns) {
                continue;
            }

            DB::table($table)->select(array_merge(['id'], $columns))->orderBy('id')->chunkById(500, function ($rows) use ($table, $columns) {
                foreach ($rows as $row) {
                    $changes = [];
                    foreach ($columns as $column) {
                        $value = $row->{$column};
                        if (is_string($value) && trim($value) !== '' && ($e164 = Phone::e164($value)) && $e164 !== $value) {
                            $changes[$column] = $e164;
                        }
                    }
                    if ($changes) {
                        DB::table($table)->where('id', $row->id)->update($changes);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Formatting change only; the E.164 values remain valid.
    }
};
