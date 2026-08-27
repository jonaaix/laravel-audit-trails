# Configuration

```bash
php artisan vendor:publish --tag="audit-trails-config"
```

Published file: `config/audit-trails.php`.

## `model`

Override the audit trail model class. Useful when you need to attach extra traits — for example multi-tenant scoping — without forking the package.

```php
'model' => \App\Models\AuditTrail::class,
```

```php
namespace App\Models;

use Aaix\LaravelAuditTrails\Models\AuditTrail as BaseAuditTrail;
use App\Concerns\BelongsToTenant;

class AuditTrail extends BaseAuditTrail
{
    use BelongsToTenant;
}
```

Your subclass **must** extend the package model so all relations and casts continue to work.

## `observer`

The observer registered on every audited model. Override it to change which events are recorded, or how a row is built:

```php
'observer' => \App\Observers\AuditTrailObserver::class,
```

```php
namespace App\Observers;

use Aaix\LaravelAuditTrails\Enums\AuditActionEnum;
use Aaix\LaravelAuditTrails\Observers\AuditTrailObserver as BaseObserver;
use Illuminate\Database\Eloquent\Model;

class AuditTrailObserver extends BaseObserver
{
    protected function log(Model $model, AuditActionEnum $action, array $changes = []): void
    {
        if ($model instanceof \App\Models\Ping) {
            return; // too noisy to audit
        }

        parent::log($model, $action, $changes);
    }
}
```

Your class **must** extend the package observer. The value is read when an audited model boots, so set it in config rather than swapping it at runtime.

For deriving extra columns, prefer a `creating` hook on the audit model — see [Usage → Derive columns from the audited record](/usage#derive-columns-from-the-audited-record).

## `table`

Rename the table:

```php
'table' => 'app_audit_log',
```

The model resolves the table from this value, and the published migration uses it too.

## `user_resolver`

Returns the user identifier (or `null`) attached to each audit row. Default:

```php
'user_resolver' => static fn (): mixed => auth()->id(),
```

Override when you need a system actor for queue/console writes:

```php
'user_resolver' => static function (): ?int {
    if ($user = auth()->user()) {
        return $user->id;
    }

    return app()->runningInConsole() ? config('audit-trails.system_user_id') : null;
},
```

The resolver is invoked at write time, so it sees the current request context.

## `ignored_attributes`

Globally strip attributes from `updated` change sets. Defaults:

```php
'ignored_attributes' => [
    'updated_at',
    'deleted_at',
    'password',
    'remember_token',
],
```

Updates that consist *only* of ignored attributes are skipped entirely — no row is written.

For per-model overrides, see [Usage → Per-model ignored attributes](/usage#per-model-ignored-attributes).
