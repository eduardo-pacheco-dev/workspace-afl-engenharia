# tests/Feature/Http/Middleware

## Purpose

This reference defines conventions for middleware feature tests under `tests/Feature/Http/Middleware`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/Feature/Http/Middleware/<Middleware>Test.php` for middleware behavior through real routes.

### File Shape

- Define a local `/_test` route inside the test.
- Attach the middleware under test directly by class or alias.
- Use Pest HTTP helpers such as `get`.
- Keep each test focused on one branch.

### Access Middleware Pattern

Cover:

- guest behavior;
- authenticated but unauthorized behavior;
- authorized behavior.

Use config overrides when the middleware reads allowlists or feature flags.

```php
it('forbids guests', function (): void {
    Route::middleware('example-access')
        ->get('/_test', fn (): string => 'ok');

    get('/_test')->assertForbidden();
});

it('forbids actors without access', function (): void {
    config(['example.allowed_identifiers' => ['allowed@example.com']]);

    Route::middleware('example-access')
        ->get('/_test', fn (): string => 'ok');

    $actor = Actor::factory()->createOne(['email' => 'blocked@example.com']);

    login($actor);

    get('/_test')->assertForbidden();
});

it('allows actors with access', function (): void {
    config(['example.allowed_identifiers' => ['allowed@example.com']]);

    Route::middleware('example-access')
        ->get('/_test', fn (): string => 'ok');

    $actor = Actor::factory()->createOne(['email' => 'allowed@example.com']);

    login($actor);

    get('/_test')->assertOk();
});
```

### Inertia Middleware Pattern

For Inertia sharing middleware:

- define a route that renders an Inertia component;
- call the route as guest and authenticated actor;
- assert shared props through `assertInertia`;
- assert nested public ids for shared auth/actor/`Workspace` data.

```php
it('shares null authentication data for guests', function (): void {
    Route::middleware(ExampleInertiaRequests::class)
        ->get('/_test', fn () => Inertia::render('example/Page'));

    get('/_test')
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page): void {
            $page
                ->where('auth.actor', null)
                ->where('auth.workspace', null);
        });
});

it('shares authenticated actor details', function (): void {
    Route::middleware(ExampleInertiaRequests::class)
        ->get('/_test', fn () => Inertia::render('example/Page'));

    $workspace = Workspace::factory()->createOne();
    $actor = login(workspace: $workspace);

    get('/_test')
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($actor, $workspace): void {
            $page
                ->where('auth.actor.id', $actor->public_id)
                ->where('auth.workspace.id', $workspace->public_id);
        });
});
```

### Do Not

- Do not call middleware methods directly when route behavior is the contract.
- Do not reuse global application routes if a local test route proves the middleware cleanly.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/Http/Middleware/README.md`
- `references/tests/Pest.md`
