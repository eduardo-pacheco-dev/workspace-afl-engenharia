# tests/Integration/Models/Concerns

## Purpose

This reference defines conventions for persisted model concern tests under `tests/Integration/Models/Concerns`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/Integration/Models/Concerns/<Concern>Test.php` for persisted behavior provided by reusable model concerns.

### Deactivation Concern Pattern

For deactivation concerns, cover the reusable persisted API once through the generic test support model:

- active and deactivated state helpers;
- `active()` and `deactivated()` local scopes;
- `deactivate()` setting `deactivated_at` without overwriting an existing timestamp;
- `reactivate()` clearing `deactivated_at` while remaining idempotent for active records.

Use compact tests that group one concern behavior owner per test. It is also acceptable to split transition and idempotence branches into separate focused tests when that is clearer in the live sibling file. Do not create separate tests for every branch when the split does not clarify the reusable concern contract.

```php
it('detects active and deactivated models', function (): void {
    $activeModel = ExampleModel::query()->create();
    $deactivatedModel = ExampleModel::query()->create(['deactivated_at' => now()]);

    expect($activeModel)
        ->isActive()->toBeTrue()
        ->isDeactivated()->toBeFalse()
        ->and($deactivatedModel)
        ->isActive()->toBeFalse()
        ->isDeactivated()->toBeTrue();
});

it('scopes active and deactivated models', function (): void {
    $activeModel = ExampleModel::query()->create();
    $deactivatedModel = ExampleModel::query()->create(['deactivated_at' => now()]);

    expect(ExampleModel::query()->active()->pluck('id')->all())
        ->toBe([$activeModel->id])
        ->and(ExampleModel::query()->deactivated()->pluck('id')->all())
        ->toBe([$deactivatedModel->id]);
});

it('keeps deactivation transitions idempotent', function (): void {
    $activeModel = ExampleModel::query()->create();
    $alreadyDeactivatedModel = ExampleModel::query()->create([
        'deactivated_at' => CarbonImmutable::today()->subDay(),
    ]);

    $activeModel->deactivate();
    $alreadyDeactivatedModel->deactivate();

    $activeModel->refresh();
    $alreadyDeactivatedModel->refresh();

    expect($activeModel)
        ->isDeactivated()->toBeTrue()
        ->deactivated_at->not->toBeNull()
        ->and($alreadyDeactivatedModel)
        ->isDeactivated()->toBeTrue()
        ->deactivated_at->toEqual(CarbonImmutable::today()->subDay());
});

it('keeps reactivation transitions idempotent', function (): void {
    $deactivatedModel = ExampleModel::query()->create(['deactivated_at' => now()]);
    $activeModel = ExampleModel::query()->create();

    $deactivatedModel->reactivate();
    $activeModel->reactivate();

    $deactivatedModel->refresh();
    $activeModel->refresh();

    expect($deactivatedModel)
        ->isActive()->toBeTrue()
        ->deactivated_at->toBeNull()
        ->and($activeModel)
        ->isActive()->toBeTrue()
        ->deactivated_at->toBeNull();
});
```

### Public Id Concern Pattern

For public-id concerns, cover:

- a public id is generated when creating a model;
- `findByPublicId()` returns the correct record;
- `findOrFailByPublicId()` throws a model-not-found exception for an unknown public id.

Do not add a standalone `wherePublicId()` scope test when the finder tests already cover the same public ID constraint. Test the scope directly only if it gains behavior distinct from the finder contract.

Use the test support model instead of an application model when the behavior is generic.

```php
it('sets public id when creating', function (): void {
    $this->mock(ExampleIdClient::class, function (MockInterface $mock): void {
        $mock->shouldReceive('formattedId')
            ->once()
            ->with(ExampleAlphabet::Alphanumeric(), 10)
            ->andReturn('abc123de45');
    });

    $model = ExampleModel::query()->create();

    expect($model)
        ->public_id->toBeString()
        ->public_id->toBe('abc123de45');
});

it('finds assigned public ids case insensitively through the database', function (): void {
    $model = ExampleModel::query()->create(['public_id' => 'AbC123dE45']);

    expect($model->is(ExampleModel::findByPublicId('abc123de45')))->toBeTrue();
});

it('throws when model is not found by public id', function (): void {
    expect(fn () => ExampleModel::findOrFailByPublicId('missing'))
        ->toThrow(ModelNotFoundException::class);
});
```

### File Shape

- Import only the exception types, collaborators, and test support models needed by the concern under test.
- Persist a generic test support model through `ExampleModel::query()->create()`.
- Assert generated identifiers are strings and have the expected length when the concern generates identifiers.
- Assert case-insensitive finder behavior when the concern stores public IDs in case-insensitive columns.
- Use `expect(fn () => ...)->toThrow(...)` for exception cases.

### Split From Feature Tests

This path proves the concern's persisted model API. Use `tests/Feature/Models/Concerns` for route model binding behavior through HTTP and routing middleware.

### System-Logic-Only Policy

This path inherits the persisted model boundary from `references/tests/Integration/Models/README.md`; keep coverage focused on reusable concern behavior that needs saved records.

## Coverage Expectations

Cover only persisted reusable-concern behavior in this path. Route binding behavior for the same concern belongs in `tests/Feature/Models/Concerns`.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not add generic Laravel relationship mechanic assertions (loading, related-model type checks, FK/ID equality, or factory/count smoke checks).

## Related References

- `references/tests/Integration/Models/README.md`
- `references/tests/Unit/Models/README.md`
