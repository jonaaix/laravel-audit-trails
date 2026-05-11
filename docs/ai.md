# AI / Laravel Boost

This package ships an [Agent Skill](https://laravel.com/docs/13.x/boost) for [Laravel Boost](https://laravel.com/ai/boost). When you (or a teammate) work on a Laravel app that depends on this package, Boost can auto-install the skill so coding agents know how to use the audit trail correctly — trait setup, query patterns, ignored attributes, soft-delete behaviour, and audit-model overrides.

## Install the skill

Inside any Laravel app that has `aaix/laravel-audit-trails` installed:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

Boost picks up the skill from `resources/boost/skills/laravel-audit-trails/SKILL.md` shipped inside this package and installs it according to your agent preference.

## What the skill covers

- Enabling auditing on a model via `TracksAuditTrail`
- Reading the polymorphic `auditTrails` relation (and why ordering is required)
- `creating_user` / `updating_user` accessors
- Per-model `$auditIgnoredAttributes` and global `ignored_attributes`
- Customising `user_resolver` for queues/console
- Soft-delete event semantics (`Deleted` / `Restored` / `ForceDeleted`)
- Extending the audit model (multi-tenant scoping etc.)
- Schema reference and `AuditActionEnum` values

## Source

The skill lives in this repo at [`resources/boost/skills/laravel-audit-trails/SKILL.md`](https://github.com/jonaaix/laravel-audit-trails/blob/main/resources/boost/skills/laravel-audit-trails/SKILL.md). Open an issue or PR if anything in it drifts from the package behaviour.
