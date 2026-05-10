<?php

declare(strict_types=1);

namespace Aaix\LaravelAuditTrails\Enums;

enum AuditActionEnum: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case ForceDeleted = 'force_deleted';
}
