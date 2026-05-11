---
name: laravel-audit-trails
description: Use the aaix/laravel-audit-trails package to record created/updated/deleted/restored/force_deleted events on Eloquent models, read the polymorphic audit trail, and customise the user resolver, ignored attributes, and audit model.
---

# Laravel Audit Trails

Polymorphic, soft-delete-aware audit logging for Eloquent. One trait per model — every lifecycle event is written to `audit_trails`.

## When to use this skill

- Adding audit logging to an Eloquent model.
- Reading the change history for a record (who/what/when).
- Customising which attributes are tracked, which user is recorded, or where rows are stored.
- Extending the `AuditTrail` model (multi-tenant scoping, extra relations).

## Enable on a model

Add the trait — that's the whole setup per model:

```php
use Aaix\LaravelAuditTrails\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use TracksAuditTrail;
}
```

From now on `created`, `updated`, `deleted`, `restored`, and `forceDeleted` events on `Order` write a row to `audit_trails`.

## Required setup (once per app)

```bash
composer require aaix/laravel-audit-trails
php artisan vendor:publish --tag="audit-trails-migrations"
php artisan migrate

# Optional: publish config to customise model/table/user_resolver/ignored_attributes
php artisan vendor:publish --tag="audit-trails-config"
```

## Read the trail

The trait exposes a polymorphic `auditTrails` `MorphMany`:

```php
$order = Order::find(1);

foreach ($order->auditTrails as $trail) {
    $trail->action;        // AuditActionEnum (Created/Updated/Deleted/Restored/ForceDeleted)
    $trail->user;          // BelongsTo to config('auth.providers.users.model'), nullable
    $trail->changes_json;  // array — populated only for Updated events
    $trail->created_at;
}
```

The relation is **unordered** by default. Always order explicitly:

```php
$order->auditTrails()->latest('id')->get();

Order::with(['auditTrails' => fn ($q) => $q->latest('id')])->find($id);
```

## Convenience accessors

```php
$order->creating_user; // first audit row's user (oldest id)
$order->updating_user; // most recent audit row's user (latest id)
```

These fire a query each. For lists, eager-load `auditTrails` and resolve the user yourself.

## Per-model ignored attributes

Hide attributes from the change log on a single model:

```php
class User extends Model
{
    use TracksAuditTrail;

    public static array $auditIgnoredAttributes = ['last_login_at', 'login_count'];
}
```

These merge with the global `audit-trails.ignored_attributes` config. An `updated` event whose changes consist *only* of ignored attributes writes no row at all.

## Global ignored attributes (config)

Default list in `config/audit-trails.php`:

```php
'ignored_attributes' => ['updated_at', 'deleted_at', 'password', 'remember_token'],
```

## User resolver

Default returns `auth()->id()`. Override for system actors in queues/console:

```php
'user_resolver' => static function (): ?int {
    if ($user = auth()->user()) {
        return $user->id;
    }
    return app()->runningInConsole() ? config('audit-trails.system_user_id') : null;
},
```

Resolver runs at write time, so it sees the current request/job context.

## Soft deletes

For models using `Illuminate\Database\Eloquent\SoftDeletes`:

- `$model->delete()` → `Deleted`
- `$model->restore()` → `Restored`
- `$model->forceDelete()` → `ForceDeleted` only (the leading `Deleted` event is suppressed)

`deleted_at` is in the default ignore list so restore/delete events stay clean.

## Override the audit model (multi-tenant etc.)

```php
// config/audit-trails.php
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

Your subclass **must** extend the package model — relations and casts depend on it.

## Schema reference

`audit_trails` table:

| Column           | Type                       | Notes                                                |
| ---------------- | -------------------------- | ---------------------------------------------------- |
| `id`             | `bigIncrements`            |                                                      |
| `action`         | `string`                   | created / updated / deleted / restored / force_deleted |
| `auditable_type` | `string`                   | morphs() — polymorphic model class                   |
| `auditable_id`   | `unsignedBigInteger`       | morphs() — polymorphic key                           |
| `user_id`        | `unsignedBigInteger`, null | from `user_resolver`                                 |
| `changes_json`   | `json`, null               | `$model->getChanges()` for `updated`                 |
| `created_at`     | `timestamp`                |                                                      |
| `updated_at`     | `timestamp`                |                                                      |

Composite index on `(auditable_type, auditable_id)` via `morphs()`.

## AuditActionEnum

```php
Aaix\LaravelAuditTrails\Enums\AuditActionEnum::Created       // 'created'
Aaix\LaravelAuditTrails\Enums\AuditActionEnum::Updated       // 'updated'
Aaix\LaravelAuditTrails\Enums\AuditActionEnum::Deleted       // 'deleted'
Aaix\LaravelAuditTrails\Enums\AuditActionEnum::Restored      // 'restored'
Aaix\LaravelAuditTrails\Enums\AuditActionEnum::ForceDeleted  // 'force_deleted'
```

`$trail->action` is cast to this enum.

## Common pitfalls

- **No rows for "noisy" updates** — if every changed column is in the ignore list, no row is written. This is intentional.
- **Unordered relation** — `$model->auditTrails` is not chronological. Use `latest('id')` / `oldest('id')`.
- **`user_id` may be null** — console/queue writes without an authenticated user, unless you customise `user_resolver`.
- **Requires PHP 8.2+ and Laravel 11+.**
- **Custom audit model must extend the package model** — don't replace, extend `Aaix\LaravelAuditTrails\Models\AuditTrail`.
