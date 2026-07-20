# tests/Pest.php

## Purpose

This reference defines conventions for `tests/Pest.php`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

`tests/Pest.php` defines global behavior and helpers for the Unit, Integration, and Feature suites. Read it before writing any test that uses authentication, toasts, frozen time, Vite behavior, lock-query assertions, or shared helper functions.

### Global Setup

- Tests extend the project `Tests\TestCase`.
- The global `beforeEach` freezes time to the current second.
- Vite is disabled globally for test requests.
- The active suites are `Feature`, `Integration`, and `Unit`.

Required entries:

- global `pest()->extend(TestCase::class)` setup for `Feature`, `Integration`, and `Unit`;
- global `assertDatabaseLockedForUpdate(...)` helper for action integration tests that must prove `lockForUpdate()`;
- global `login(...)` helper for authenticated tests;
- `TestResponse::macro('assertToast', ...)` for redirect toast assertions.

### Shared Login Helper

Use `login()` instead of hand-writing `actingAs()` in feature and integration tests that need an authenticated actor.

- `login()` creates an actor when none is provided.
- `login(workspace: $workspace)` creates an actor with that `Workspace` relationship when needed.
- `login($actor)` authenticates a specific existing actor.
- The helper returns the authenticated `Actor`, so keep it when assertions need the actor's public id or `Workspace` state.
- For controller validation tests, pass an authorized in-scope `Workspace` to `login(...)` so validation runs instead of being hidden by authorization or binding failures.

### Lock Query Assertions

Use `assertDatabaseLockedForUpdate(ParentRecord::class)` before the code under test when an action integration test must prove `lockForUpdate()` was applied to a specific table. Pass a model class, model instance, table name, or a list of those values, plus an optional connection name when needed. Like Laravel's database assertions, model classes are resolved to their table names and model connections. Like Laravel's query-count helper, it registers the listener before the code under test and asserts the SQL shape before the application is destroyed.

### Toast Assertions

Use `assertToast()` for redirects that attach the shared toast flash data.

Expected pattern:

```php
$response->assertRedirectToRoute('route.name', [
    'workspace' => $workspace,
])
    ->assertToast('Resource created');
```

The macro asserts the exact flash payload shape under `inertia.flash_data.toast`, including title, variant, timeout, and optional description.

### Implications

- Do not add duplicate helper functions inside individual test files.
- Do not manually call `withoutVite()` in ordinary tests.
- Use frozen time directly in date assertions unless a test explicitly changes time.

## Coverage Expectations

Use these helpers instead of per-file helper duplication. If a new helper is needed, first prove the pattern repeats across multiple current test files.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/tests/README.md`
