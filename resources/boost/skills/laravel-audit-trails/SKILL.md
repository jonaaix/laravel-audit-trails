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

## Derive columns from the audited record

Rows are saved with `auditable` pre-loaded — read it in a `creating` hook on your audit model, never query by `auditable_id`:

```php
static::creating(fn (self $trail) => $trail->tenant_id = $trail->auditable?->tenant_id);
```

`deleted` fires after the `DELETE`, so without `SoftDeletes` a lookup returns null and a `NOT NULL` column fails with SQLSTATE 1364.

The relation stays loaded, so `toArray()`/`toJson()` embed the audited record, and `ignored_attributes` does **not** filter it (only the audited model's `$hidden` does). To opt out, call `$trail->unsetRelation('auditable')` at the end of the `creating` hook — not after `save()`, since `created`/`saved` fire inside it.

## Gotchas

- `user_id` is null when no resolver match (e.g. console without override).
- Extend `Aaix\LaravelAuditTrails\Models\AuditTrail` if overriding via `config('audit-trails.model')` — don't replace.
- Same for `config('audit-trails.observer')` — extend `AuditTrailObserver`, override `log()`.
- Requires PHP 8.3+ / Laravel 13+.
