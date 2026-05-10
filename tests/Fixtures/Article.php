<?php

declare(strict_types=1);

namespace Aaix\LaravelAuditTrails\Tests\Fixtures;

use Aaix\LaravelAuditTrails\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;
    use TracksAuditTrail;

    protected $guarded = [];

    public static array $auditIgnoredAttributes = ['secret'];
}
