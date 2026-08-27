<?php

declare(strict_types=1);

use Aaix\LaravelAuditTrails\Enums\AuditActionEnum;
use Aaix\LaravelAuditTrails\Models\AuditTrail;
use Aaix\LaravelAuditTrails\Observers\AuditTrailObserver;
use Aaix\LaravelAuditTrails\Tests\Fixtures\CustomAuditTrailObserver;
use Aaix\LaravelAuditTrails\Tests\Fixtures\Note;

beforeEach(function (): void {
    CustomAuditTrailObserver::reset();
});

it('defaults to the package observer', function (): void {
    expect(config('audit-trails.observer'))->toBe(AuditTrailObserver::class);

    Note::create(['title' => 'Hello']);

    expect(CustomAuditTrailObserver::$seen)->toBe([])
        ->and(AuditTrail::query()->count())->toBe(1);
});

it('registers the observer configured in audit-trails.observer', function (): void {
    config()->set('audit-trails.observer', CustomAuditTrailObserver::class);

    $note = Note::create(['title' => 'Hello']);
    $note->delete();

    expect(CustomAuditTrailObserver::$seen)->toBe([
        AuditActionEnum::Created->value,
        AuditActionEnum::Deleted->value,
    ])->and(AuditTrail::query()->count())->toBe(2);
});
