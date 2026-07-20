# tests/TestCase.php

## Purpose

This reference defines conventions for `tests/TestCase.php`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

`tests/TestCase.php` defines database, configuration, routing, HTTP, and morph-map behavior shared by all test suites.

### Database Behavior

- Tests use lazy database refreshes.
- Fresh migrations include both `database/migrations` and `tests/migrations`.
- Generic support models should use test migrations rather than application migrations when proving reusable concerns.

### Reference Data Database

- The base `setUp()` does not provision the non-default reference-data database. It only calls `parent::setUp()`, `Http::preventStrayRequests()`, and the morph map.
- The reference-data SQLite database is provisioned outside the test lifecycle (CI workflow, `composer` scripts, and the container entrypoint). The command feature test that exercises the database download sets the connection per test.
- Reference-data model tests can assume the non-default connection is configured, but should keep queries bounded with `first()`, limited relationships, or focused queries.

### HTTP Behavior

- `Http::preventStrayRequests()` is enabled.
- Every test path that touches external HTTP must call `Http::fake()` or mock the SDK/service that would make the request.
- Do not let a test depend on a live provider, local network, or DNS.

### Morph Maps

- The test case enforces a morph map for test support models.
- Package/support tests should use the provided test model when the behavior is generic and does not belong to an application model.

### Example

```php
<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithCachedConfig;
use Illuminate\Foundation\Testing\WithCachedRoutes;
use Illuminate\Support\Facades\Http;
use Tests\Support\Models\ExampleModel;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase {
        migrateFreshUsing as baseMigrateFreshUsing;
    }
    use WithCachedConfig;
    use WithCachedRoutes;

    protected function migrateFreshUsing(): array
    {
        return array_merge($this->baseMigrateFreshUsing(), [
            '--path' => [
                'database/migrations',
                'tests/migrations',
            ],
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        Relation::enforceMorphMap([
            'example_model' => ExampleModel::class,
        ]);
    }
}
```

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/tests/README.md`
- `references/tests/migrations/README.md`
- `references/tests/Support/Models/README.md`
- `references/tests/TestSupport/README.md`
