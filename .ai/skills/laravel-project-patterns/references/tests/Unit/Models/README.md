# tests/Unit/Models

## Purpose

This reference defines conventions for `tests/Unit/Models`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/Unit/Models/<Model>Test.php` for model-level behavior that does not need a persisted relationship graph.

### What Belongs Here

- Trait presence such as public-id or soft-delete traits.
- Trait presence for lifecycle concerns such as `HasDeactivation`; persisted transition behavior belongs in `tests/Integration/Models/Concerns`.
- Cast behavior using `new Model([...])`.
- Model defaults from `$attributes`.
- Pure accessors such as display names.
- Simple methods that can be tested with in-memory models or config fakes.
- Prunable query construction when the assertion can be focused and fast. Existing baseline tests may create minimal records to prove the prunable query result; broader persistence behavior belongs in `tests/Integration/Models`.
- Pure normalization helpers, such as code normalization, when no database state is needed.
- Cast shape for enums, immutable timestamps, arrays, floats, and value objects such as phone numbers.

### File Shape

- Import only the model, enums, traits, casts, and framework utilities needed.
- Use one `it(...)` per behavior.
- Keep names precise: `it('uses SoftDeletes trait')`, `it('correctly casts attributes')`, `it('sets model defaults')`.

### Cast Tests

Use raw assigned values and assert the hydrated cast result:

```php
$model = new SomeModel([
    'created_at' => '2026-03-30 15:33:00',
    'amount' => 1.2,
    'status' => 'active',
]);

expect($model)
    ->created_at->toBeInstanceOf(CarbonImmutable::class)
    ->amount->toBe('1.20')
    ->status->toBe(Status::Active);
```

Decimal casts are strings. Date casts should be immutable where the app config makes dates immutable.
Float casts should assert `toBeFloat()` rather than an exact database-formatted decimal. Phone number casts can assert the value object type here; formatting belongs in resource or controller persistence tests.

### Trait Tests

Use `class_uses_recursive(Model::class)` and assert the exact trait class.
Do not duplicate the reusable concern's method and scope behavior in every consuming model test.

### Relationship Split

Do not test loaded relationship graphs here. Use `tests/Integration/Models` for persisted domain behavior such as observer side effects, factory coherence that matters to business behavior, route key persistence, and cross-model behavior.

Do not test database constraints here. For example, coordinate range checks and active-name uniqueness are integration model tests because they require direct persistence against the database.

### Integration Boundary

For persisted model behavior, load `references/tests/Integration/Models/README.md`; do not repeat integration policy in unit-model tests.

## Coverage Expectations

Use this path for class-local model contracts only. If the assertion needs `createOne()` for a saved graph, first check whether it is actually system behavior; otherwise it probably belongs in a controller/resource/action test or should not be added.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/Models/README.md`
- `references/tests/Integration/Models/README.md`
