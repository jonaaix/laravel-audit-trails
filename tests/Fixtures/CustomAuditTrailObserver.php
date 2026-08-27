<?php

declare(strict_types=1);

namespace Aaix\LaravelAuditTrails\Tests\Fixtures;

use Aaix\LaravelAuditTrails\Enums\AuditActionEnum;
use Aaix\LaravelAuditTrails\Observers\AuditTrailObserver;
use Illuminate\Database\Eloquent\Model;

class CustomAuditTrailObserver extends AuditTrailObserver
{
    /**
     * @var list<string>
     */
    public static array $seen = [];

    public static function reset(): void
    {
        static::$seen = [];
    }

    protected function log(Model $model, AuditActionEnum $action, array $changes = []): void
    {
        static::$seen[] = $action->value;

        parent::log($model, $action, $changes);
    }
}
