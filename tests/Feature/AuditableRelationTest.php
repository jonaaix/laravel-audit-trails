<?php

declare(strict_types=1);

use Aaix\LaravelAuditTrails\Enums\AuditActionEnum;
use Aaix\LaravelAuditTrails\Models\AuditTrail;
use Aaix\LaravelAuditTrails\Tests\Fixtures\Article;
use Aaix\LaravelAuditTrails\Tests\Fixtures\CapturingAuditTrail;
use Aaix\LaravelAuditTrails\Tests\Fixtures\Note;
use Aaix\LaravelAuditTrails\Tests\Fixtures\TenantAuditTrail;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    config()->set('audit-trails.model', CapturingAuditTrail::class);
    config()->set('audit-trails.user_resolver', static fn (): ?int => 42);

    CapturingAuditTrail::reset();
});

it('hands the audited model to the audit row when a model without soft deletes is hard-deleted', function (): void {
    $note = Note::create(['title' => 'Hello']);
    CapturingAuditTrail::reset();

    $note->delete();

    expect(Note::query()->count())->toBe(0);

    $captured = CapturingAuditTrail::$captured;

    expect($captured)->toHaveCount(1)
        ->and($captured[0]['action'])->toBe(AuditActionEnum::Deleted->value)
        ->and($captured[0]['auditable'])->not->toBeNull()
        ->and($captured[0]['auditable'])->toBeInstanceOf(Note::class)
        ->and($captured[0]['auditable']->getKey())->toBe($note->getKey())
        ->and($captured[0]['auditable']->title)->toBe('Hello');

    expect(CapturingAuditTrail::query()->where('action', AuditActionEnum::Deleted)->count())->toBe(1);
});

it('hands the audited model to the audit row on created and updated', function (): void {
    $note = Note::create(['title' => 'Hello']);
    $note->update(['title' => 'World']);

    $captured = CapturingAuditTrail::$captured;

    expect($captured)->toHaveCount(2)
        ->and($captured[0]['action'])->toBe(AuditActionEnum::Created->value)
        ->and($captured[0]['auditable']?->getKey())->toBe($note->getKey())
        ->and($captured[1]['action'])->toBe(AuditActionEnum::Updated->value)
        ->and($captured[1]['auditable']?->getKey())->toBe($note->getKey())
        ->and($captured[1]['auditable']->title)->toBe('World');
});

it('hands the audited model to the audit row on deleted, restored and force-deleted', function (): void {
    $article = Article::create(['title' => 'Hello']);
    CapturingAuditTrail::reset();

    $article->delete();
    $article->restore();
    $article->forceDelete();

    $captured = CapturingAuditTrail::$captured;

    expect(array_column($captured, 'action'))->toBe([
        AuditActionEnum::Deleted->value,
        AuditActionEnum::Restored->value,
        AuditActionEnum::ForceDeleted->value,
    ]);

    foreach ($captured as $entry) {
        expect($entry['auditable'])->toBeInstanceOf(Article::class)
            ->and($entry['auditable']->getKey())->toBe($article->getKey());
    }
});

it('lets a consumer derive a NOT NULL column off a hard-deleted record', function (): void {
    config()->set('audit-trails.model', TenantAuditTrail::class);

    $note = Note::create(['title' => 'Hello', 'tenant_id' => 7]);
    $note->delete();

    $trail = TenantAuditTrail::query()->where('action', AuditActionEnum::Deleted)->sole();

    expect($trail->tenant_id)->toBe(7);
});

it('keeps the relation loaded, so serialising the row embeds the audited record', function (): void {
    // Documented trade-off of pre-loading, pinned here so it stays deliberate.
    // Opting out is the consumer's call, inside their own `creating` hook.
    $hookInstance = null;

    CapturingAuditTrail::created(function (CapturingAuditTrail $t) use (&$hookInstance): void {
        $hookInstance = $t;
    });

    $note = Note::create(['title' => 'Hello', 'tenant_id' => 7]);
    $note->update(['title' => 'World']);

    expect($hookInstance)->not->toBeNull()
        ->and($hookInstance->relationLoaded('auditable'))->toBeTrue()
        ->and($hookInstance->toArray())->toHaveKey('auditable')
        ->and($hookInstance->toArray()['auditable']['title'])->toBe('World');

    // A row loaded back from the database is unaffected.
    expect(CapturingAuditTrail::query()->latest('id')->first()->toArray())
        ->not->toHaveKey('auditable');
});

it('lets a consumer opt out by unsetting the relation in its own creating hook', function (): void {
    config()->set('audit-trails.model', AuditTrail::class);

    $hookInstance = null;

    AuditTrail::creating(function (AuditTrail $t): void {
        // Derive whatever is needed from $t->auditable first, then drop it.
        $t->unsetRelation('auditable');
    });
    AuditTrail::created(function (AuditTrail $t) use (&$hookInstance): void {
        $hookInstance = $t;
    });

    Note::create(['title' => 'Hello']);

    expect($hookInstance)->not->toBeNull()
        ->and($hookInstance->relationLoaded('auditable'))->toBeFalse()
        ->and($hookInstance->toArray())->not->toHaveKey('auditable');
});

it('pre-loads the relation so reading it in a creating hook fires no query', function (): void {
    $note = Note::create(['title' => 'Hello']);
    CapturingAuditTrail::reset();

    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $note->delete();

    $selectsOnNotes = array_filter(
        $queries,
        static fn (string $sql): bool => str_starts_with(strtolower($sql), 'select') && str_contains($sql, 'notes'),
    );

    expect(CapturingAuditTrail::$captured[0]['relation_was_loaded'])->toBeTrue()
        ->and($selectsOnNotes)->toBe([]);
});
