<?php

declare(strict_types=1);

namespace Aaix\LaravelAuditTrails;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelAuditTrailsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('audit-trails')
            ->hasConfigFile('audit-trails')
            ->hasMigration('create_audit_trails_table');
    }
}
