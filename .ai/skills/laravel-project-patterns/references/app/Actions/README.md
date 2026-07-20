# app/Actions

## Purpose

This reference defines project conventions for container-resolved domain actions under `app/Actions`.

## When To Use

Use this reference when creating or changing an action class, action input object, generated-value action, lifecycle action, or action integration test.

## Required Pattern

Use `app/Actions` for domain actions that coordinate persistence, generated values, retries, lifecycle transitions, transactional guards, or cross-record state changes outside HTTP controllers.

### Action Shape

- Use strict types, constructor injection, and explicit return types.
- Prefer a public `handle(...)` entrypoint for domain actions, while preserving existing framework contract method names such as `create`, `reset`, and `update`.
- Framework contract actions may keep framework-owned validation, validation bags, notification side effects, and `forceFill(...)->save()` when that is the package contract pattern. Normal app-owned actions should use `$model->update([...])` or relationship creates.
- For action input that needs typed transformation, omitted-field semantics, or persistence-ready field mapping, prefer a dedicated input object over passing raw arrays through the action boundary.
- Keep retry limits, expiration windows, and similar invariants as typed constants on the action.
- Use transactions when the action coordinates cleanup plus creation, lifecycle changes with guards, default selection, pivot changes, or multiple persisted side effects.
- Prefer database constraints for business invariants that PostgreSQL can enforce. Use `lockForUpdate()` only for a documented cross-row invariant that cannot reasonably be expressed in the database, and make every competing action lock the same parent row in the same order.
- Keep helpers private unless another application surface, framework contract, stub, or trait actually consumes or exposes them.

### Framework Contract Actions

Actions that implement framework contracts may keep the framework method name instead of `handle(...)`, such as `create(...)`, `reset(...)`, or `update(...)`. They may also keep framework-owned validation, validation bags, notification side effects, and `forceFill(...)->save()` when the package contract or sibling implementation uses that shape.

Do not copy those framework exceptions into app-owned domain actions. Normal app-owned actions should use `handle(...)`, typed arguments or Data inputs, and `$model->update(...)` or relationship creates for persistence.

### Action Inputs

For actions that mutate an already-bound model, do not accept route parents or owners only to prove ownership again. The entrypoint's scoped binding and policy own that boundary. Mutate the bound model directly unless the action owns another reason to query fresh database state:

```php
public function handle(ParentRecord $parentRecord, UpdateParentRecordInput $input): ParentRecord
{
    $parentRecord->update($input->transform());

    return $parentRecord;
}
```

Lifecycle and delete actions should remain direct when they perform one mutation. Keep a transaction when the action coordinates multiple writes or an action-owned guard with the mutation:

```php
public function handle(ParentRecord $parentRecord): void
{
    DB::transaction(function () use ($parentRecord): void {
        if ($parentRecord->children()->withTrashed()->exists()) {
            throw CannotDeleteParentRecord::becauseItHasDependencies();
        }

        $parentRecord->delete();
    });
}
```

### Create And Child Mutations

Create actions accept the parent or `Workspace` model when that model is the direct business input for creating the new row:

```php
public function handle(ParentRecord $parentRecord, CreateChildRecordInput $input): ChildRecord
{
    return $parentRecord->children()->create($input->transform());
}
```

For update, delete, and lifecycle mutations, accept the child model directly. The entrypoint owns route hierarchy, ownership, authorization, and soft-delete binding. This keeps actions usable from commands, jobs, tests, and Tinker without reconstructing the HTTP route hierarchy.

If the action needs a parent or owner for a business rule, derive that relationship from the child. Do not make the caller pass it merely to re-query the same child. Query fresh state only for an action-owned transactional guard, lock, or required relationship read.

```php
public function handle(
    ChildRecord $childRecord,
    UpdateChildRecordInput $input,
): ChildRecord {
    return tap($childRecord)->update($input->transform());
}
```

When creating a leaf record under a child, the child remains a direct business input. Derive any parent needed by a business guard, and keep user-facing range checks in private helpers. Back cross-row uniqueness and non-overlap rules with database constraints:

```php
public function handle(ChildRecord $childRecord, CreateLeafRecordInput $input): LeafRecord
{
    return DB::transaction(function () use ($childRecord, $input): LeafRecord {
        $parentRecord = $childRecord->parentRecord()->firstOrFail();

        if ($parentRecord->deactivated_at !== null) {
            throw CannotCreateLeafRecord::becauseParentIsDeactivated();
        }

        $this->ensureRangeIsAvailable($childRecord, $input);

        return $childRecord->leaves()->create($input->transform());
    });
}
```

Do not move transactional business guards into broad model helpers just to reduce a repeated query. Keep application behavior in the action unless a local reusable abstraction already exists for that exact domain.

### Data Inputs

When using Spatie Laravel Data action inputs, place them close to the action namespace, such as `app/Actions/<Domain>/Inputs/<Verb><Model>Input.php`.

Laravel Data action inputs should extend `Spatie\LaravelData\Data`, use constructor-promoted readonly properties, and use `#[MapName(SnakeCaseMapper::class)]` when the HTTP/request payload is snake_case but PHP properties are camelCase.

Keep Data input constructor types aligned with the Form Request contract that creates them. Nullable request fields become nullable input properties; optional/omitted request fields become `Optional` unions. Do not add `Optional` just because a field is nullable.

Use `Optional` for omitted fields on partial updates. On create inputs, use `Optional` only for fields the request may omit and that should let the model `$attributes` defaults apply instead of forcing the default in the action.

Write Eloquent persistence with `$input->transform()` when the action needs mapped output names and omitted `Optional` fields excluded from the array. Prefer `transform()` over `toArray()` in action persistence code because it states that the input is being transformed for output before the Eloquent write. Do not manually duplicate model defaults inside the action when the model already owns them.

```php
#[MapName(SnakeCaseMapper::class)]
final class UpdateParentRecordInput extends Data
{
    public function __construct(
        public readonly null|Optional|string $description,
        public readonly Optional|string $name,
        public readonly bool|Optional $enabled,
    ) {
    }
}
```

### Generated Values And Resolvers

For generated identifiers, keep the generated-value logic private and check uniqueness against the specific application contract. Scope generated values through the owner when the value is owner-scoped; normalize before querying when the persisted column is normalized. Do not add an active-state filter unless the domain allows reuse from inactive records; default Eloquent soft-delete scopes are enough when soft-deleted records may be ignored. For globally unique single-use codes, clean up existing unused rows for the submitted identifier before creating a new one.

```php
class GenerateChildRecordCode
{
    public const int MAX_RETRY_ATTEMPTS = 20;

    public function __construct(private readonly CodeGenerator $codeGenerator)
    {
    }

    public function handle(ParentRecord $parentRecord): string
    {
        $attempts = 0;

        while ($attempts < self::MAX_RETRY_ATTEMPTS) {
            $code = $this->generateCode($parentRecord);

            ++$attempts;

            if (! $this->codeExists($parentRecord, $code)) {
                return $code;
            }
        }

        throw CannotGenerateChildRecordCode::maxAttempts($attempts);
    }

    private function codeExists(ParentRecord $parentRecord, string $code): bool
    {
        return ChildRecord::query()
            ->where('parent_record_id', $parentRecord->id)
            ->where('normalized_code', ChildRecord::normalizeCode($code))
            ->exists();
    }
}
```

Resolver actions can return nullable models when the domain contract is finder-only:

```php
public function handle(ParentRecord $parentRecord, string $code): null|ChildRecord
{
    return ChildRecord::query()
        ->where('parent_record_id', $parentRecord->id)
        ->where('normalized_code', ChildRecord::normalizeCode($code))
        ->whereNull('deactivated_at')
        ->first();
}
```

### Test Mapping

- Action behavior is covered through `tests/Integration/Actions` when the action itself owns branching, persistence, retries, or collaborator coordination.
- Resolve actions from the container in tests.
- Mock injected collaborators through the container.
- Instantiate Data inputs in action tests with `Input::from([...])` so casting, name mapping, and `Optional` behavior are exercised at the action boundary.
- Keep action tests ordered from guard/failure/domain-exception cases to success cases. For create inputs, add a required-only or omitted-fields success case only when omitted `Optional` fields or model defaults are behavior owned by the action.
- Name the primary create success case after the created record, such as `creates a child record`. Keep parent or owner qualifiers out of the name unless parent scope is the behavior being proved; assert ordinary parent persistence in the test body instead.
- When an exceptional action owns a documented lock, test the business guard that requires serialization. Do not add a query-shape test merely because the action emits `FOR UPDATE`.
- Do not add ownership-mismatch or stale-binding integration tests when the entrypoint owns those boundaries. Test action-owned behavior: persistence, domain exceptions, idempotent lifecycle transitions, and side effects.
- Cover the applicable behavior owned by the action: success, cleanup, retry collisions, `Workspace` or parent isolation only when the action owns that invariant, soft-delete or active-state semantics, and domain exception limits when those branches exist.
- When a transactional or dependent-record guard moves out of a Form Request, the action owns the guard test. Keep the controller feature test for the HTTP entry point and exception-to-validation mapping; do not delete it as duplicate coverage.

## Coverage Expectations

Examine actual source files in `app/Actions/`, compare them with similar action files, and write tests in the appropriate test suite that owns that behavior. Do not duplicate tests across multiple suites purely for structural symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/README.md`
- `references/tests/Integration/Actions/README.md`
