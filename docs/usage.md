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

## Soft deletes

For models using `Illuminate\Database\Eloquent\SoftDeletes`, the package emits:

- `Deleted` on `$model->delete()`
- `Restored` on `$model->restore()`
- `ForceDeleted` on `$model->forceDelete()` (and the leading `Deleted` event is suppressed)

Changes to the `deleted_at` column are ignored by default to keep restore/delete events clean.
