<?php

declare(strict_types=1);

namespace Aaix\LaravelAuditTrails\Tests;

use Aaix\LaravelAuditTrails\LaravelAuditTrailsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LaravelAuditTrailsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('secret')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->timestamps();
        });

        // Mirrors a consumer that denormalises an inherited scope column onto the
        // audit row. `tenant_id` is NOT NULL on purpose: deriving it from a lookup
        // fails here with SQLSTATE 1364 once the audited row is gone.
        Schema::create('tenant_audit_trails', function (Blueprint $table): void {
            $table->id();
            $table->string('action');
            $table->morphs('auditable');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->json('changes_json')->nullable();
            $table->timestamps();
        });

        Schema::create(config('audit-trails.table', 'audit_trails'), function (Blueprint $table): void {
            $table->id();
            $table->string('action');
            $table->morphs('auditable');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->json('changes_json')->nullable();
            $table->timestamps();
        });
    }
}
