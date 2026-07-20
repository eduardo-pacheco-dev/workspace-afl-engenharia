# Project Supporting PHP

## Purpose

This reference defines conventions for PHP files that support the project outside the main application, schema, resource, email, and test suites.

## When To Use

Use this reference when changing:

- `routes/*.php`
- `config/*.php`
- `lang/**/*.php`
- `bootstrap/*.php`
- `public/*.php`
- `database/seeders/*.php`
- root tooling PHP such as `rector.php` and dependency-analysis config
- auxiliary PHP guidance files tracked with the repository

## Required Pattern

Supporting PHP files still use strict types, explicit imports, typed closures where practical, and the smallest configuration surface that matches the live framework contract.

### Routes

Keep route files declarative. Group names, prefixes, middleware, and scoped bindings before route declarations. Use controller classes directly for invokable routes, `[Controller::class, 'method']` for method routes, and named resource routes for controller feature-test contracts.

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CurrentActorController;
use App\Http\Controllers\Api\SessionCodeController;
use App\Http\Controllers\ExampleRecordController;
use App\Http\Controllers\ExampleRecordLifecycleController;
use App\Http\Controllers\WorkspaceSettingsController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function (): void {
    Route::name('sessions.')->prefix('sessions')->group(function (): void {
        Route::post('code/request', SessionCodeController::class)
            ->middleware(['throttle:sessions.code.request'])
            ->name('code.request');
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('actor', [CurrentActorController::class, 'show'])
            ->name('actor.show');
    });
});

Route::middleware('auth')->group(function (): void {
    Route::middleware('verified')->scopeBindings()->group(function (): void {
        Route::get('workspaces/{workspace}/settings/general', [WorkspaceSettingsController::class, 'show'])
            ->name('workspaces.settings.general');

        Route::singleton('workspaces.example-records.lifecycle', ExampleRecordLifecycleController::class)
            ->creatable()
            ->only(['destroy', 'store']);

        Route::resource('workspaces.example-records', ExampleRecordController::class);
    });
});
```

Route names, route parameter order, and `scopeBindings()` are part of the controller feature-test contract. If a route changes, update the matching controller tests and controller reference examples.

Console route files may schedule framework/application commands. Keep schedules declarative and use the narrow cadence the operational contract needs:

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('reference-data:update')->weekly();

Schedule::command('health:check')->everyMinute();
Schedule::command('health:queue-check-heartbeat')->everyMinute();
Schedule::command('health:schedule-check-heartbeat')->everyMinute();

Schedule::command('model:prune')->daily();
```

### Bootstrap And Public Entrypoints

Keep bootstrap and public entrypoints thin. They should wire framework routing, middleware aliases, exception integration, and request handling. Do not put application business logic in `bootstrap/*.php` or `public/*.php`.

```php
<?php

declare(strict_types=1);

use App\Http\Middleware\ExamplePageMiddleware;
use App\Support\ExampleExceptionIntegration;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware
            ->alias([
                'example-admin' => ExamplePageMiddleware::class,
            ])
            ->redirectGuestsTo(fn (): string => route('login'))
            ->redirectUsersTo('/')
            ->throttleApi()
            ->web(append: [
                ExamplePageMiddleware::class,
            ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        ExampleExceptionIntegration::handles($exceptions);
    })->create();
```

For long-running server entrypoints, set only required server path defaults and then require the framework worker. Keep request-specific state out of static variables and globals.

### Configuration Files

Configuration files return arrays and may import framework/package classes when the config value is a class-string contract. Cast environment values at the config boundary when the consuming package expects a scalar type.

```php
<?php

declare(strict_types=1);

use App\Support\Files\ExampleFileNamer;
use App\Support\Files\ExamplePathGenerator;
use Illuminate\Support\Str;

return [
    'default' => env('EXAMPLE_DISK', 'local'),

    'prefix' => env('EXAMPLE_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-example-'),

    'enabled' => (bool) env('EXAMPLE_ENABLED', true),

    'retry_after' => (int) env('EXAMPLE_RETRY_AFTER', 210),

    'sample_rate' => env('EXAMPLE_SAMPLE_RATE') === null ? 1.0 : (float) env('EXAMPLE_SAMPLE_RATE'),

    'file_namer' => ExampleFileNamer::class,
    'path_generator' => ExamplePathGenerator::class,

    'emails' => [
        ...array_filter(
            explode(',', (string) env('EXAMPLE_EMAILS', '')),
            static fn (string $email): bool => $email !== '',
        ),
    ],
];
```

Prefer local helper functions already used by the config file, such as safe URL parsing or multibyte string trimming, instead of ad hoc parsing. Keep package defaults recognizable unless the application intentionally overrides them.

### Localization Files

Localization files are strict PHP arrays. Use enum values as translation keys when the enum owns a public label map, and use action keys for toast or validation messages consumed by controllers, resources, requests, actions, or exceptions.

```php
<?php

declare(strict_types=1);

use App\Enums\ExampleStatus;

return [
    'created' => [
        'title' => 'Record created',
    ],

    'updated' => [
        'title' => 'Record updated',
    ],

    'validation' => [
        'locked' => 'This record cannot be changed right now.',
    ],

    'statuses' => [
        ExampleStatus::Active->value => 'Active',
        ExampleStatus::Inactive->value => 'Inactive',
    ],
];
```

When adding a translated enum label, keep the enum helper and unit tests aligned with the translation key shape.

### Seeders

Use seeders for local/demo data only unless a task explicitly requires production seed data. Keep ownership graphs coherent by creating the top-level `Workspace`, attaching actors, and then creating nested records through factories and relationships.

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Actor;
use App\Models\ExampleRecord;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class LocalSeeder extends Seeder
{
    public function run(): void
    {
        $actor = Actor::factory()->createOne([
            'email' => 'actor@example.com',
        ]);

        $workspaces = Workspace::factory()
            ->count(2)
            ->for($actor, 'owner')
            ->create();

        $actor->workspaces()->sync($workspaces);

        $workspaces->each(function (Workspace $workspace): void {
            ExampleRecord::factory()
                ->for($workspace)
                ->count(3)
                ->create();
        });
    }
}
```

Do not use seeders as a substitute for factory states or tests.

### Tooling PHP

Root tooling PHP should be declarative and bounded to the repository paths it owns.

```php
<?php

declare(strict_types=1);

use Example\Tooling\Config;

return Config::configure()
    ->withCache('./.cache/example')
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/bootstrap/cache',
    ]);
```

When changing tooling config, verify the configured paths still include every source area the tool is meant to own and still exclude generated/cache output.

## Coverage Expectations

Read the exact supporting PHP file and the nearest related application/test reference before changing behavior. For route changes, update controller/API/controller-test references and tests. For config changes, test the behavior through the surface that consumes the config. For localization changes, update enum/unit tests, request/controller tests, or exception/action tests that assert the translated message.

## Do Not

- Do not put business logic in route, config, bootstrap, public entrypoint, or tooling files.
- Do not add real module/entity examples to this reference.
- Do not treat generated/cache PHP as source evidence.

## Related References

- `references/app/README.md`
- `references/app/Providers/README.md`
- `references/tests/README.md`
- `references/tests/Feature/Http/Controllers/README.md`
