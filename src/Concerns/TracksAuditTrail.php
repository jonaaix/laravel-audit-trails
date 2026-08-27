<?php

declare(strict_types=1);

namespace Aaix\LaravelAuditTrails\Concerns;

use Aaix\LaravelAuditTrails\Models\AuditTrail;
use Aaix\LaravelAuditTrails\Observers\AuditTrailObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait TracksAuditTrail
{
    public static function bootTracksAuditTrail(): void
    {
        static::whenBooted(static fn () => static::observe(static::resolveAuditTrailObserver()));
    }

    public function auditTrails(): MorphMany
    {
        return $this->morphMany(self::resolveAuditTrailModel(), 'auditable');
    }

    public function getCreatingUserAttribute(): ?Model
    {
        return $this->auditTrails()->oldest('id')->first()?->user;
    }

    public function getUpdatingUserAttribute(): ?Model
    {
        return $this->auditTrails()->latest('id')->first()?->user;
    }

    /**
     * @return class-string<AuditTrail>
     */
    protected static function resolveAuditTrailModel(): string
    {
        /** @var class-string<AuditTrail> $class */
        $class = config('audit-trails.model', AuditTrail::class);

        return $class;
    }

    /**
     * @return class-string<AuditTrailObserver>
     */
    protected static function resolveAuditTrailObserver(): string
    {
        /** @var class-string<AuditTrailObserver> $class */
        $class = config('audit-trails.observer', AuditTrailObserver::class);

        return $class;
    }
}
