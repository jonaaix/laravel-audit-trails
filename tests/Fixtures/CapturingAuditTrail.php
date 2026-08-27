<?php

declare(strict_types=1);

namespace Aaix\LaravelAuditTrails\Tests\Fixtures;

use Aaix\LaravelAuditTrails\Enums\AuditActionEnum;
use Aaix\LaravelAuditTrails\Models\AuditTrail;
use Illuminate\Database\Eloquent\Model;

/**
 * Stands in for a consumer model that denormalises a column off the audited
 * record inside a `creating` hook.
 */
class CapturingAuditTrail extends AuditTrail
{
    /**
     * @var list<array{action: mixed, auditable: ?Model, relation_was_loaded: bool}>
     */
    public static array $captured = [];

    public static function reset(): void
    {
        static::$captured = [];
    }

    protected static function booted(): void
    {
        static::creating(function (self $trail): void {
            $action = $trail->getAttribute('action');

            static::$captured[] = [
                // Read *before* touching the relation, so it reports whether the
                // observer pre-loaded it rather than whether a lazy load ran.
                'relation_was_loaded' => $trail->relationLoaded('auditable'),
                'action' => $action instanceof AuditActionEnum ? $action->value : $action,
                'auditable' => $trail->auditable,
            ];
        });
    }
}
