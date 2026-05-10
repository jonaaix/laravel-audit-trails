---
layout: home

hero:
  name: Laravel Audit Trails
  text: Lightweight audit logging for Eloquent models
  tagline: Track created, updated, deleted, restored and force-deleted events with one trait.
  image:
    src: /logo.webp
    alt: Laravel Audit Trails
  actions:
    - theme: brand
      text: Get Started
      link: /installation
    - theme: alt
      text: View on GitHub
      link: https://github.com/jonaaix/laravel-audit-trails

features:
  - title: One trait
    details: Add TracksAuditTrail to a model — created, updated, deleted, restored and force-deleted events are logged automatically.
  - title: Soft-delete aware
    details: Restore and force-delete are tracked as distinct actions, deleted_at noise is filtered out of change sets.
  - title: Filter what you store
    details: Global and per-model ignored attributes keep secrets and timestamp churn out of the audit log.
---
