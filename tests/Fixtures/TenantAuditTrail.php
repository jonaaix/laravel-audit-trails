<?php

declare(strict_types=1);

namespace Aaix\LaravelAuditTrails\Tests\Fixtures;

use Aaix\LaravelAuditTrails\Models\AuditTrail;

/**
 * The consumer shape this package change exists for: a NOT NULL column
 * denormalised off the audited record inside a `creating` hook.
 */
class TenantAuditTrail extends AuditTrail
{
    protected $table = 'tenant_audit_trails';

    protected static function booted(): void
    {
        static::creating(function (self $trail): void {
            $trail->tenant_id = $trail->auditable?->tenant_id;
        });
    }
}
