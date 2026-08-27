<?php

declare(strict_types=1);

namespace Aaix\LaravelAuditTrails\Tests\Fixtures;

use Aaix\LaravelAuditTrails\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;

/**
 * Deliberately without SoftDeletes: a `delete()` here is a hard delete, so the
 * row is gone by the time the `deleted` event fires.
 */
class Note extends Model
{
    use TracksAuditTrail;

    protected $guarded = [];
}
