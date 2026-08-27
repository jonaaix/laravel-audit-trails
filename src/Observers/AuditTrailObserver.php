<?php

declare(strict_types=1);

namespace Aaix\LaravelAuditTrails\Observers;

use Aaix\LaravelAuditTrails\Enums\AuditActionEnum;
use Aaix\LaravelAuditTrails\Models\AuditTrail;
use Illuminate\Database\Eloquent\Model;

class AuditTrailObserver
{
    public function created(Model $model): void
    {
        $this->log($model, AuditActionEnum::Created);
    }

    public function updated(Model $model): void
    {
        $changes = $this->filterIgnored($model, $model->getChanges());

        if ($changes === []) {
            return;
        }

        $this->log($model, AuditActionEnum::Updated, $changes);
    }

    public function deleted(Model $model): void
    {
        if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
            return;
        }

        $this->log($model, AuditActionEnum::Deleted);
    }

    public function restored(Model $model): void
    {
        $this->log($model, AuditActionEnum::Restored);
    }

    public function forceDeleted(Model $model): void
    {
        $this->log($model, AuditActionEnum::ForceDeleted);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    protected function log(Model $model, AuditActionEnum $action, array $changes = []): void
    {
        /** @var class-string<AuditTrail> $modelClass */
        $modelClass = config('audit-trails.model', AuditTrail::class);

        $audit = new $modelClass([
            'action' => $action->value,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'user_id' => $this->resolveUserId(),
            'changes_json' => $changes ?: null,
        ]);

        // Hand the audited instance to the audit row as a pre-loaded relation, so
        // hooks on the audit model can derive columns from it without a lookup.
        // On a hard delete the row is already gone by the time `deleted` fires —
        // this instance is the only remaining source of truth.
        $audit->setRelation('auditable', $model);

        $audit->save();
    }

    protected function resolveUserId(): mixed
    {
        $resolver = config('audit-trails.user_resolver');

        return is_callable($resolver) ? $resolver() : null;
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    protected function filterIgnored(Model $model, array $changes): array
    {
        $globalIgnored = (array) config('audit-trails.ignored_attributes', []);
        $modelIgnored = (array) ($model::$auditIgnoredAttributes ?? []);

        $ignored = array_unique([...$globalIgnored, ...$modelIgnored]);

        return array_diff_key($changes, array_flip($ignored));
    }
}
