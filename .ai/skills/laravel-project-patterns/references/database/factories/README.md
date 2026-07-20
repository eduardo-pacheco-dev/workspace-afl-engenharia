# Factories

## Purpose

This reference defines project conventions for Laravel model factories.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use this reference when creating or changing Laravel model factories.

### Factory Shape

The names below are shape-only placeholders. Keep examples synthetic in this reference; when editing real code, preserve the live ownership graph, defaults, casts, and constraints from sibling factories.

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ChildRecord;
use App\Models\ParentRecord;
use App\Models\RelatedRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChildRecord>
 */
class ChildRecordFactory extends Factory
{
    public function deactivated(): static
    {
        return $this->state(['deactivated_at' => now()]);
    }

    public function definition(): array
    {
        $minimumAmount = fake()->randomFloat(2, 0, 10);

        return [
            'parent_record_id' => ParentRecord::factory(),

            'deactivated_at' => null,
            'maximum_amount' => fake()->optional()->randomFloat(2, $minimumAmount, 20),
            'minimum_amount' => $minimumAmount,
            'name' => fake()->word(),
        ];
    }
}
```

### Core Rules

- Include strict types and `@extends Factory<Model>`.
- Use model factories for required parent IDs: `'parent_record_id' => ParentRecord::factory()`.
- Group relationship/ownership IDs before regular attributes, separated by a blank line.
- Factory defaults should create valid, ordinary active records.
- In tests, do not pass attributes that the factory already generates unless that exact value is part of the assertion, query, validation failure, redirect target, or serialized contract under test.
- Use enum instances or enum values consistently with sibling factories and casts.
- Prefer realistic fake data that satisfies validation and database constraints.
- Prefer `fake()->unique()` where uniqueness is required by schema or behavior. Fields with app-level uniqueness expectations may also use it to keep fixtures stable even when the database does not enforce a matching unique constraint.
- When a factory repeatedly needs an expensive deterministic default such as a hashed password, a static cache property is acceptable if sibling factories use it and the cached value is not test-specific.
- For partial unique constraints that tests may hit repeatedly in the same owner scope, make the constrained factory value unique by default, such as `fake()->unique()->word()` for active names or normalized codes. Override explicit duplicates only in tests that assert the uniqueness behavior.
- Use `createOne()` in tests for single persisted records; use `count(...)->create()` only when multiple records are the subject of the assertion.
- Use `createOne()` for single model instances that are passed to route helpers, route model binding, or controller redirect assertions, including mocked action return values used to build redirect routes. Do not set generated route keys such as `public_id`, `slug`, or generated codes unless the literal value is asserted; factories generate valid route keys. Set only relationships and non-generated attributes required by that contract and, when the persisted row can affect validation, choose non-conflicting domain values. Use `makeOne()` only when the test explicitly needs an unsaved model that is not used as a route parameter.
- Use state methods for meaningful domain variants such as `deactivated()`, `default()`, `expired()`, `used()`, `openEnded()`, or `roundUp()`.
- Return `static` from state methods unless sibling factories use `self`.
- Default lifecycle columns such as `deactivated_at` to `null` and add a named state like `deactivated()` instead of making inactive records randomly.
- When one fake value constrains another, compute them together so defaults always pass validation and database constraints. Examples include minimum/maximum pairs, range bands, and latitude/longitude values generated with `fake()->latitude()` and `fake()->longitude()`.
- For address-like factories, use the local region-data concern when siblings do so region, subdivision, locality, and contact-number values remain coherent; add a deterministic state such as `forRegion(RegionCode $regionCode)` when callers need a fixed region.

State method examples:

```php
public function forRegion(RegionCode $regionCode): static
{
    return $this->state(['region_code' => $regionCode]);
}

public function openEnded(): static
{
    return $this->state(['maximum_amount' => null]);
}

public function roundUp(): static
{
    return $this->state([
        'rounding_increment' => 1,
        'rounding_mode' => RoundingMode::Up,
    ]);
}
```

### Relationship Coherence

When a child model also stores denormalized ownership, derive it from the parent to avoid inconsistent test data:

```php
'workspace_id' => fn (array $attributes): int => ParentRecord::query()
    ->findOrFail($attributes['parent_record_id'])
    ->workspace_id,
```

Default factories must create coherent ownership graphs. Only create mismatched parent/`Workspace` graphs in tests that explicitly assert route-binding, listing, authorization, or validation behavior for invalid ownership. For denormalized ownership cases, derive redundant `Workspace` values from the direct parent by default, then intentionally override them only in boundary tests.

When an optional `belongsTo` model must share ownership, use an `afterCreating` hook with `associate(...)`. The example below is shape-only; `relatedRecord()` represents a `belongsTo` relation:

```php
public function withRelatedRecord(): static
{
    return $this->afterCreating(function (ChildRecord $childRecord): void {
        $childRecord->relatedRecord()->associate(
            RelatedRecord::factory()->createOne([
                'workspace_id' => $childRecord->workspace_id,
            ])
        );

        $childRecord->save();
    });
}
```

When a factory needs to create or sync a pivot after the model exists, do it in `afterCreating` and keep the caller-controlled values explicit:

```php
/**
 * @param array<string, mixed> $values
 */
public function withWorkspace(null|Workspace $workspace = null, array $values = []): static
{
    $workspace ??= Workspace::factory()->createOne();

    return $this->state(['current_workspace_id' => $workspace->id])
        ->afterCreating(function (Actor $actor) use ($workspace, $values): void {
            $actor->workspaces()->syncWithPivotValues($workspace, $values);
        });
}
```

### Factory Relationship APIs

- Use `for($model)` for belongs-to relationships.
- Use `has(RelatedFactory::factory(), 'relationshipName')` when the relationship name is not the Laravel default.
- Use `recycle($model)` only when it does not hide the ownership graph being proven. It is acceptable to recycle a shared ancestor in a binding, listing, or validation test when the purpose is to isolate a lower boundary and the expected graph is still explicit in the assertion.
- Use `sequence(...)` to create related rows that differ only in small attributes.
- Use `afterCreating` when relationship creation must inspect the persisted parent or attach/sync pivot values.

Use `recycle($workspace)` when testing a non-ownership property of a child model and a prior test or fixture guarantees every sibling factory shares that `Workspace`. Avoid `recycle($model)` when it would conceal the relationship under test; create the related records explicitly when the assertion must prove the graph.

### Anti-Patterns

- Do not manually create parent records in every test when the factory can express the relationship.
- Do not create records with mismatched ownership IDs unless the test is explicitly about invalid or unrelated data.
- Do not leave nullable optional fields randomly non-null if tests need predictable default behavior; default them to `null` and add a state for non-null variants when useful.
- Do not use `recycle()` as a shortcut to hide ownership setup or avoid asserting the relationship graph under test.

Factory guidance should not reintroduce model-integration relationship smoke tests. Use `references/tests/Integration/Models/README.md` for the canonical persisted model boundary.

## Coverage Expectations

Read the factory source file being tested, compare it with sibling factories, and cover only behavior owned by that factory or its matching test reference. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/Models/README.md`
- `references/database/migrations/README.md`
- `references/tests/Integration/Models/README.md`
