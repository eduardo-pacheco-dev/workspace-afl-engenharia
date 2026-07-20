# tests/Integration/Models

## Purpose

This reference defines conventions for persisted model tests under `tests/Integration/Models`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/Integration/Models/<Model>Test.php` for persisted model behavior, relationships, observers, factories, slugs, `Workspace` ownership coherence, and model methods that require saved records.

### System Logic Tests

Only keep model integration tests that prove persisted system behavior. Good examples include:

- observer-managed state, such as default child selection;
- model methods that require persisted records or saved relationship state;
- slugs, route keys, state transitions, and persisted side effects;
- domain-scoped relationships where the scope itself is business logic, such as selecting only the default child.
- local query scopes only when the scope itself owns system behavior that is not already covered by a public method, finder, controller path, or broader persisted behavior.
- `Workspace`/current-`Workspace` state transitions that cannot be proven on an in-memory model.
- database-enforced business invariants, such as a soft-delete-aware unique constraint that must block direct persistence outside the controller path.
- database check constraints that protect domain ranges, such as latitude and longitude geographic bounds.

For database-enforced uniqueness, assert the direct write is blocked without going through controller validation:

```php
it('enforces non-deleted code uniqueness per Workspace', function (): void {
    $record = Record::factory()->createOne([
        'normalized_code' => 'ABC123',
    ]);

    expect(fn () => Record::factory()->recycle($record->workspace)->createOne([
        'normalized_code' => 'ABC123',
    ]))->toThrow(QueryException::class);
});
```

When adding or changing a database constraint with named failure modes, assert the constraint name in the exception message so the test proves the intended invariant:

```php
it('enforces the latitude geographic range at the database level', function (): void {
    expect(fn () => ParentRecord::factory()->createOne([
        'latitude' => 91,
        'longitude' => 0,
    ]))->toThrow(QueryException::class, 'parent_records_latitude_range_check');
});
```

For non-soft-deleted name uniqueness, mirror the actual database predicate:

- duplicate non-soft-deleted records in the same `Workspace` throw the named unique constraint;
- soft-deleted records can be replaced when the index excludes deleted rows;
- deactivated but not deleted records remain reserved when the index does not exclude `deactivated_at`.

Do not add tests that only prove Laravel relationship loading or schema wiring, such as:

- FK/ID equality between related models;
- `->toBeInstanceOf(RelatedModel::class)`;
- `->toHaveCount(n)` because a factory created `n` children;
- mirrored parent/child relationship tests without distinct system behavior.
- generic index or column existence checks with no domain behavior attached.
- direct tests for simple query-scope wrappers when another tested public API already proves the same constraint.

### Related Model Rule

When adding or changing a relationship, update related model tests only when the change introduces or alters system behavior. Do not add paired relationship tests solely to prove both Eloquent relationship directions load.

### Observer And Side-Effect Tests

Use this path for observer-managed state, such as:

- only one default child record per parent;
- preserving existing data when create/update fails;
- behavior with soft-deleted records;
- cross-parent isolation;
- updates that should not trigger observer logic.

Refresh models before asserting persisted side effects.

### Factory Coherence

When a model belongs to a `Workspace` through multiple relationships, factories and tests must keep the ownership graph coherent. Do not mix a child from one `Workspace` with a parent from another unless the test is deliberately asserting a 404/403 failure elsewhere.

### Slugs And Route Keys

Slug generation and non-regeneration on update belong here when implemented by model attributes or observers.

```php
it('generates a slug when creating a Workspace', function (): void {
    $workspace = Workspace::factory()->createOne([
        'name' => 'Example Workspace',
    ]);

    expect($workspace->slug)->toBe('example-workspace');
});

it('does not regenerate the slug when the Workspace name changes', function (): void {
    $workspace = Workspace::factory()->createOne([
        'name' => 'Example Workspace',
    ]);

    $workspace->update([
        'name' => 'Updated Workspace',
    ]);

    expect($workspace->refresh()->slug)->toBe('example-workspace');
});
```

### Current Workspace State

Use this path when a model method persists or rejects current-`Workspace` state.

```php
it('sets current_workspace_id when loading currentWorkspace if it is not already set', function (): void {
    $actor = Actor::factory()
        ->has(Workspace::factory(), 'workspaces')
        ->createOne();

    expect($actor->current_workspace_id)->toBeNull();

    $actor->currentWorkspace;

    expect($actor->fresh()->current_workspace_id)->not->toBeNull();
});

it('cannot switch to an unrelated Workspace', function (): void {
    $unrelatedWorkspace = Workspace::factory()->createOne();
    $actor = Actor::factory()->createOne();

    expect($actor)
        ->switchWorkspace($unrelatedWorkspace)->toBeFalse()
        ->currentWorkspace->toBeNull();
});

it('can switch to a related Workspace', function (): void {
    $workspace = Workspace::factory()->createOne();
    $actor = Actor::factory()->withWorkspace($workspace)->createOne();

    expect($actor)
        ->switchWorkspace($workspace)->toBeTrue()
        ->currentWorkspace->is($workspace)->toBeTrue();
});
```

### System-Logic-Only Policy

tests/Integration/Models/\*\* is for persisted system behavior only: observers, model methods requiring saved records, slugs/route keys, state transitions, persisted side effects, and domain-scoped relationship logic. Do not test generic Laravel mechanics such as relationship loading, related-model type checks, FK/ID equality, or factory/count smoke checks.

## Coverage Expectations

Cover only persisted system behavior for models in this path.
Do not add coverage here as a proxy for controller binding, resource serialization, or factory relationship wiring. Put those tests in the path that owns the behavior.
When a model invariant has both direct database enforcement and HTTP validation, keep both tests: integration proves the database cannot be bypassed; controller feature tests prove HTTP validation and redirects.
Do not collapse those into one suite unless one layer no longer owns the behavior. Invalid duplication is re-testing the same owner twice, not proving the same invariant through its database and HTTP contracts.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not add generic Laravel relationship mechanic assertions (loading, related-model type checks, FK/ID equality, or factory/count smoke checks).

## Related References

- `references/app/Models/README.md`
- `references/tests/Unit/Models/README.md`
