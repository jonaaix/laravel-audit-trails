<?php

declare(strict_types=1);

use Aaix\LaravelAuditTrails\Models\AuditTrail;
use Aaix\LaravelAuditTrails\Observers\AuditTrailObserver;

return [

    /*
    |--------------------------------------------------------------------------
    | Audit trail model
    |--------------------------------------------------------------------------
    |
    | Override this if you want to extend the default model — for example to
    | attach additional traits (multi-tenant scoping, soft deletes, …) or to
    | add custom relations. Your class must extend the package model.
    |
    */
    'model' => AuditTrail::class,

    /*
    |--------------------------------------------------------------------------
    | Audit trail observer
    |--------------------------------------------------------------------------
    |
    | The observer registered on every audited model. Override this to change
    | which events are recorded or how a row is built — for example to skip
    | noisy actions or to enrich the payload. Your class must extend the
    | package observer.
    |
    */
    'observer' => AuditTrailObserver::class,

    /*
    |--------------------------------------------------------------------------
    | Database table
    |--------------------------------------------------------------------------
    */
    'table' => 'audit_trails',

    /*
    |--------------------------------------------------------------------------
    | User resolver
    |--------------------------------------------------------------------------
    |
    | Returns the user identifier (or null) to attach to each audit row.
    | Override this when you need a system actor for queue/console writes,
    | or when authentication lives outside the default guard.
    |
    */
    'user_resolver' => static fn (): mixed => auth()->id(),

    /*
    |--------------------------------------------------------------------------
    | Globally ignored attributes
    |--------------------------------------------------------------------------
    |
    | Attributes listed here are stripped from "updated" change sets across
    | every audited model. Per-model overrides are available by defining a
    | public static $auditIgnoredAttributes array on the model.
    |
    */
    'ignored_attributes' => [
        'updated_at',
        'deleted_at',
        'password',
        'remember_token',
    ],

];
