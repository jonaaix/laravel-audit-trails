# Installation

## Requirements

- PHP **8.2+**
- Laravel **11.0+**

## Install via Composer

```bash
composer require aaix/laravel-audit-trails
```

The service provider is auto-discovered, so no manual registration is needed.

## Publish the migration

```bash
php artisan vendor:publish --tag="audit-trails-migrations"
php artisan migrate
```

This creates the `audit_trails` table:

| Column           | Type                       | Notes                                  |
| ---------------- | -------------------------- | -------------------------------------- |
| `id`             | `bigIncrements`            | Primary key                            |
| `action`         | `string`                   | One of created/updated/deleted/restored/force_deleted |
| `auditable_type` | `string`                   | Polymorphic — model class                          |
| `auditable_id`   | `unsignedBigInteger`       | Polymorphic — model PK                             |
| `user_id`        | `unsignedBigInteger`, null | Resolved by `user_resolver` config     |
| `changes_json`   | `json`, null               | `getChanges()` payload for `updated`   |
| `created_at`     | `timestamp`                |                                        |
| `updated_at`     | `timestamp`                |                                        |

A composite index on `(auditable_type, auditable_id)` is created via `morphs()`.

## Publish the config (optional)

```bash
php artisan vendor:publish --tag="audit-trails-config"
```

The published file lives at `config/audit-trails.php` — see [Configuration](/configuration).
