<?php

declare(strict_types=1);

namespace Aaix\LaravelAuditTrails\Models;

use Aaix\LaravelAuditTrails\Enums\AuditActionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditTrail extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return $this->table ?? config('audit-trails.table', 'audit_trails');
    }

    protected function casts(): array
    {
        return [
            'action' => AuditActionEnum::class,
            'changes_json' => 'array',
        ];
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}
