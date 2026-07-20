# tests/ArchitectureTest.php

## Purpose

This reference defines conventions for `tests/ArchitectureTest.php`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use architecture tests for repository-wide static expectations, not for normal behavioral coverage.

### Existing Rules

- PHP preset is enabled.
- Laravel preset is enabled with explicit local exceptions.
- Security preset is enabled with local exceptions.
- The `App` namespace must use strict types.

### Example

```php
<?php

declare(strict_types=1);

use App\Providers\ExampleServiceProvider;

arch()->preset()->php();

arch()->preset()->laravel()->ignoring([
    'App\Support\ExampleMetadata',
    ExampleServiceProvider::class,
]);

arch()->preset()->security()->ignoring('assert');

arch('strict types')
    ->expect('App')
    ->toUseStrictTypes();
```

### When To Add Or Edit

Add architecture coverage only when introducing a rule that should apply broadly across namespaces, for example:

- all application classes should use strict types;
- a namespace should not depend on another namespace;
- a class category should implement a framework contract;
- a prohibited function or pattern should not appear in production code.

### What Not To Put Here

- Do not assert resource JSON shapes here.
- Do not assert route behavior here.
- Do not assert model relationships here.
- Do not use architecture tests as a substitute for Unit, Integration, or Feature tests.

If a rule needs fixtures, database state, HTTP, or factories, it belongs in another suite.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/tests/README.md`
