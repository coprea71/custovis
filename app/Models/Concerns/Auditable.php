<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Writes an audit_logs entry for security-relevant model changes (11.md).
 *
 * Whitelist, not blacklist: only attributes listed in $auditFields are
 * logged with old/new values; attributes in $auditSecretFields are only
 * reported as "changed" (never their value). Everything else is ignored,
 * so a newly added column can never leak into the log by accident.
 *
 * Using models define:
 *   protected array $auditFields = [...];
 *   protected array $auditSecretFields = [...];   (optional)
 *   protected array $auditEvents = ['created', 'updated', 'deleted'];   (optional)
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function ($model) use ($event) {
                if (in_array($event, $model->auditEvents ?? ['created', 'updated', 'deleted'], true)) {
                    $model->writeAudit($event);
                }
            });
        }
    }

    protected function writeAudit(string $event): void
    {
        $meta = $event === 'updated' ? $this->auditChanges() : ['fields' => $this->only($this->auditFields ?? [])];

        if ($event === 'updated' && $meta === []) {
            return; // nothing security-relevant changed
        }

        AuditLog::record(
            Str::snake(class_basename($this)).'.'.$event,
            Auth::guard('web')->user() instanceof User ? Auth::guard('web')->user() : null,
            $this->auditTeam(),
            $this,
            $meta
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function auditChanges(): array
    {
        $changed = array_keys($this->getChanges());
        $fields = array_values(array_intersect($changed, $this->auditFields ?? []));
        $secrets = array_values(array_intersect($changed, $this->auditSecretFields ?? []));

        return array_filter([
            'changes' => collect($fields)->mapWithKeys(fn (string $field) => [
                $field => ['from' => $this->getOriginal($field), 'to' => $this->getAttribute($field)],
            ])->all(),
            'secrets_changed' => $secrets,
        ]);
    }

    private function auditTeam(): ?Team
    {
        return $this->getAttribute('team_id') ? Team::query()->find($this->getAttribute('team_id')) : null;
    }
}
