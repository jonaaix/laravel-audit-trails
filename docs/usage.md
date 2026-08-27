# Usage

## Enable auditing on a model

Add the `TracksAuditTrail` trait to any Eloquent model:

```php
use Aaix\LaravelAuditTrails\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use TracksAuditTrail;
}
```

That's it. From now on, every `created`, `updated`, `deleted`, `restored` and `forceDeleted` event for `Order` is logged into the `audit_trails` table.

## Read the trail

The trait exposes a polymorphic `auditTrails` relation:

```php
$order = Order::find(1);

foreach ($order->auditTrails as $trail) {
    $trail->action;        // AuditActionEnum
    $trail->user;          // related user, or null
    $trail->changes_json;  // array — only present for updated events
    $trail->created_at;    // when it happened
}
```

The relation is unordered by default — add `->latest()` or `->oldest()` as needed:

```php
Order::with(['auditTrails' => fn ($q) => $q->latest('id')])->get();
```

## Convenience accessors

```php
$order->creating_user;  // first audit row's user
$order->updating_user;  // most recent audit row's user
```

Simple accessors that hit the audit log. For high-traffic pages prefer eager-loading the `auditTrails` relation and resolving the user yourself.

## Per-model ignored attributes

Hide attributes from the change log on a single model by adding a public static array:

```php
class User extends Model
{
    use TracksAuditTrail;

    public static array $auditIgnoredAttributes = ['last_login_at', 'login_count'];
}
```

These merge with the global `audit-trails.ignored_attributes` config — see [Configuration](/configuration).

## Derive columns from the audited record

Every audit row is saved with the audited model already attached as a loaded `auditable` relation. Read it in a `creating` hook on your own audit model to denormalise a column onto the row:

```php
namespace App\Models;

use Aaix\LaravelAuditTrails\Models\AuditTrail as BaseAuditTrail;

class AuditTrail extends BaseAuditTrail
{
    protected static function booted(): void
    {
        static::creating(function (self $trail): void {
            $trail->tenant_id = $trail->auditable?->tenant_id;
        });
    }
}
```

Point `config('audit-trails.model')` at the subclass and add the column to your migration — the same pattern covers any inherited scope column (company, branch, region).

::: warning Do not look the record up by `auditable_id`
Eloquent fires `deleted` **after** the `DELETE` statement. For a model without `SoftDeletes` the row is already gone, so a lookup returns `null` and a `NOT NULL` column fails the insert with SQLSTATE 1364. The attached instance is the only remaining source of truth.
:::

Because the relation is pre-loaded, reading `$trail->auditable` costs no query — on deletes, and on every other action too.

### If you serialise the audit row

The relation stays loaded, so `toArray()` / `toJson()` on that instance embed the audited model:

```json
{"action":"deleted","auditable_id":7,"auditable":{"id":7,"title":"…"}}
```

This matters in two cases:

- **You broadcast or queue the audit row from a hook.** The payload grows by the whole audited record.
- **The audited model carries sensitive attributes.** `ignored_attributes` only filters `changes_json`; it does **not** apply to the attached instance. Only the audited model's own `$hidden` protects it here.

To opt out, unset the relation at the end of your own `creating` hook, once you've derived what you need:

```php
static::creating(function (self $trail): void {
    $trail->tenant_id = $trail->auditable?->tenant_id;

    $trail->unsetRelation('auditable');
});
```

::: tip Why in the hook, and not after the write
`created` and `saved` fire *inside* `save()`. Unsetting after the save returns would be too late for anything those hooks do — the `creating` hook is the last point that runs before serialisation becomes possible.
:::

## Soft deletes

For models using `Illuminate\Database\Eloquent\SoftDeletes`, the package emits:

- `Deleted` on `$model->delete()`
- `Restored` on `$model->restore()`
- `ForceDeleted` on `$model->forceDelete()` (and the leading `Deleted` event is suppressed)

Changes to the `deleted_at` column are ignored by default to keep restore/delete events clean.
