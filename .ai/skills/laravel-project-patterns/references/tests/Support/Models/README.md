# tests/Support/Models

## Purpose

This reference defines conventions for test-only Eloquent models under `tests/Support/Models`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/Support/Models` for test-only Eloquent models that prove generic traits, morph maps, or package integration.

### Model Rules

- Keep the model minimal.
- Use strict types.
- Back the support model with a migration under `tests/migrations`.
- Keep factories out unless repeated setup makes them necessary.
- Use the model only from tests that need a generic fixture.

```php
<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use App\Models\Concerns\HasExampleId;
use App\Models\Concerns\HasExampleState;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Override;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Table('example_models', timestamps: false)]
class ExampleModel extends Model implements HasMedia
{
    use HasExampleId;
    use HasExampleState;
    use InteractsWithMedia;

    #[Override]
    protected function casts(): array
    {
        return [
            'disabled_at' => 'datetime',
        ];
    }
}
```

### Current Usage Pattern

The test support model is used for:

- deactivation concern integration tests;
- public-id concern integration tests;
- route-binding feature tests;
- package integration tests that need a generic model;
- morph map enforcement in the base test case.

### Do Not

- Do not add application domain fields to a generic test support model.
- Generic concern fields such as `public_id` or `deactivated_at` are allowed when they are minimal schema needed for concern tests.
- Do not use an application model when the behavior being tested is framework/package infrastructure.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/tests/migrations/README.md`
- `references/tests/Integration/Models/Concerns/README.md`
- `references/tests/Feature/Models/Concerns/README.md`
