---
name: laravel-audit-trails
description: Use aaix/laravel-audit-trails to log Eloquent lifecycle events (created/updated/deleted/restored/force_deleted) and read the polymorphic audit trail.
---

# Laravel Audit Trails

Add the trait — every lifecycle event writes a row to `audit_trails`.

```php
use Aaix\LaravelAuditTrails\Concerns\TracksAuditTrail;

class Order extends Model { use TracksAuditTrail; }
```

## Read

```php
$order->auditTrails()->latest('id')->get();
// $trail->action (AuditActionEnum), ->user, ->changes_json (array, Updated only), ->created_at
```

The `auditTrails` relation is **unordered** — always add `latest('id')` / `oldest('id')`.

Accessors: `$order->creating_user`, `$order->updating_user` (one query each, don't use in loops).

## Ignore attributes

Per-model:
```php
public static array $auditIgnoredAttributes = ['last_login_at'];
```
Global: `config('audit-trails.ignored_attributes')` — defaults already cover `updated_at`, `deleted_at`, `password`, `remember_token`. An update consisting only of ignored attributes writes no row.

## Customise user

`config('audit-trails.user_resolver')` — defaults to `auth()->id()`. Override for console/queue actors.

## Soft deletes

`delete()` → `Deleted`, `restore()` → `Restored`, `forceDelete()` → `ForceDeleted` only (no leading `Deleted`).

## Gotchas

- `user_id` is null when no resolver match (e.g. console without override).
- Extend `Aaix\LaravelAuditTrails\Models\AuditTrail` if overriding via `config('audit-trails.model')` — don't replace.
- Requires PHP 8.3+ / Laravel 13+.
