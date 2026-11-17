<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Records who created, changed or deleted key records (brief §24 "audit logs").
 * Secrets and noise (passwords, tokens, timestamps) are never stored.
 */
class AuditObserver
{
    private const IGNORED = ['password', 'remember_token', 'updated_at', 'created_at', 'deleted_at', 'last_read_at', 'last_read_message_id'];

    public function created(Model $model): void
    {
        $this->write('created', $model, null, $this->clean($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = $this->clean($model->getChanges());
        if (! $changes) {
            return;
        }
        $old = array_intersect_key($this->clean($model->getOriginal()), $changes);
        $this->write('updated', $model, $old, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->write('deleted', $model, $this->clean($model->getOriginal()), null);
    }

    public function restored(Model $model): void
    {
        $this->write('restored', $model, null, null);
    }

    public static function record(string $event, ?Model $subject = null, ?array $new = null, ?int $userId = null): void
    {
        (new self)->write($event, $subject, null, $new, $userId);
    }

    private function write(string $event, ?Model $model, ?array $old, ?array $new, ?int $userId = null): void
    {
        try {
            $request = app()->runningInConsole() ? null : request();

            AuditLog::create([
                'user_id' => $userId ?? auth()->id(),
                'event' => $event,
                'auditable_type' => $model ? $model::class : null,
                'auditable_id' => $model?->getKey(),
                'old_values' => $old ?: null,
                'new_values' => $new ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : 'console',
                'url' => $request ? mb_substr($request->fullUrl(), 0, 500) : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Auditing must never break the action being audited.
            Log::warning('Audit log write failed: ' . $e->getMessage());
        }
    }

    private function clean(array $values): array
    {
        $values = array_diff_key($values, array_flip(self::IGNORED));

        return array_map(fn ($v) => is_string($v) && mb_strlen($v) > 500 ? mb_substr($v, 0, 500) . '…' : $v, $values);
    }
}
