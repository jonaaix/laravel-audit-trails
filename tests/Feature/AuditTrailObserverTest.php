<?php

declare(strict_types=1);

use Aaix\LaravelAuditTrails\Enums\AuditActionEnum;
use Aaix\LaravelAuditTrails\Models\AuditTrail;
use Aaix\LaravelAuditTrails\Tests\Fixtures\Article;
use Aaix\LaravelAuditTrails\Tests\Fixtures\User;

beforeEach(function (): void {
    config()->set('auth.providers.users.model', User::class);
    config()->set('audit-trails.user_resolver', static fn (): ?int => 42);
});

it('logs created event', function (): void {
    $article = Article::create(['title' => 'Hello']);

    $trail = AuditTrail::query()->sole();

    expect($trail->action)->toBe(AuditActionEnum::Created)
        ->and($trail->auditable_id)->toBe($article->id)
        ->and($trail->auditable_type)->toBe(Article::class)
        ->and($trail->user_id)->toBe(42)
        ->and($trail->changes_json)->toBeNull();
});

it('logs updated event with structured array', function (): void {
    $article = Article::create(['title' => 'Hello']);
    $article->update(['title' => 'World']);

    $updated = AuditTrail::query()->where('action', AuditActionEnum::Updated)->sole();

    expect($updated->changes_json)->toBeArray()
        ->and($updated->changes_json['title'])->toBe('World');
});

it('strips globally ignored attributes', function (): void {
    config()->set('audit-trails.ignored_attributes', ['updated_at', 'body']);

    $article = Article::create(['title' => 'Hello']);
    $article->update(['title' => 'A', 'body' => 'B']);

    $updated = AuditTrail::query()->where('action', AuditActionEnum::Updated)->sole();

    expect($updated->changes_json)->toHaveKey('title')
        ->and($updated->changes_json)->not->toHaveKey('body')
        ->and($updated->changes_json)->not->toHaveKey('updated_at');
});

it('strips per-model ignored attributes', function (): void {
    $article = Article::create(['title' => 'Hello']);
    $article->update(['title' => 'A', 'secret' => 'shhh']);

    $updated = AuditTrail::query()->where('action', AuditActionEnum::Updated)->sole();

    expect($updated->changes_json)->toHaveKey('title')
        ->and($updated->changes_json)->not->toHaveKey('secret');
});

it('skips updates that contain only ignored attributes', function (): void {
    $article = Article::create(['title' => 'Hello']);

    AuditTrail::query()->delete();

    $article->touch();

    expect(AuditTrail::query()->count())->toBe(0);
});

it('logs deleted, restored and force-deleted events for soft-deleting models', function (): void {
    $article = Article::create(['title' => 'Hello']);
    AuditTrail::query()->delete();

    $article->delete();
    $article->restore();
    $article->forceDelete();

    $actions = AuditTrail::query()->orderBy('id')->pluck('action')->all();

    expect($actions)->toBe([
        AuditActionEnum::Deleted,
        AuditActionEnum::Restored,
        AuditActionEnum::ForceDeleted,
    ]);
});

it('round-trips changes_json without double encoding', function (): void {
    $article = Article::create(['title' => 'Hello']);
    $article->update(['title' => 'World']);

    $row = AuditTrail::query()->where('action', AuditActionEnum::Updated)->sole();

    $rawJson = $row->getRawOriginal('changes_json');
    $decoded = json_decode((string) $rawJson, true);

    expect($decoded)->toBeArray()
        ->and($decoded['title'] ?? null)->toBe('World');
});

it('exposes auditTrails morph relation', function (): void {
    $article = Article::create(['title' => 'Hello']);
    $article->update(['title' => 'World']);

    expect($article->auditTrails()->count())->toBe(2);
});

it('resolves creating user via accessor', function (): void {
    User::create(['id' => 42, 'name' => 'Tester']);

    config()->set('audit-trails.user_resolver', static fn (): int => 42);

    $article = Article::create(['title' => 'Hello']);

    expect($article->creating_user)->not->toBeNull()
        ->and($article->creating_user->id)->toBe(42);
});
