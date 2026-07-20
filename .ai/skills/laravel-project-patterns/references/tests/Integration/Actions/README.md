# tests/Integration/Actions

## Purpose

This reference defines conventions for action integration tests under `tests/Integration/Actions`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/Integration/Actions/<Action>Test.php` for action classes that coordinate persistence, transactions, retries, external collaborators, or domain exceptions.

### File Shape

- Import the action, models, exceptions, enum collaborators, and `Mockery\MockInterface` when mocking container services.
- Resolve handle-based actions from the container with `resolve(ActionClass::class)->handle(...)`.
- Pass typed action inputs with `<ActionInput>::from([...])` when the action accepts a Data input. This keeps name mapping, casts, and `Optional` behavior in the test path.
- Use factories for persisted setup.
- Use `assertDatabaseHas()` when the durable database side effect is the contract.
- Use `$model->refresh()` with `expect()` only when the action contract includes reloaded Eloquent behavior, such as casts, accessors, relationships, timestamps, or dirty/original state.
- Do not duplicate ordinary field assertions with both `assertDatabaseHas()` and `expect($model->refresh()->field)`. Choose the assertion that matches the contract.
- Order action tests from guard/failure/domain-exception cases to success cases. Put the primary success path before extra success variants when no failure cases exist.
- Name the primary create success case after the created record, such as `creates a child record`. Do not append the parent or owner merely because the row persists its ID; use scope qualifiers only for behavior such as cross-parent isolation or an active-parent guard.
- Prefer direct database-constraint coverage for invariants enforced by PostgreSQL. When an exceptional action owns a documented lock, test the business guard that requires serialization instead of only asserting the emitted SQL shape.
- Match tests to the action boundary. Do not add ownership-mismatch tests when the entrypoint's scoped bindings and policies own that boundary. Test action-owned persistence, domain exceptions, idempotent lifecycle behavior, and side effects.
- Create actions may test `Workspace`/parent persistence because the parent model is business input for the new row.
- Test ownership, scoped binding, authorization, and soft-delete lookup failures at the entrypoint instead of recreating the route hierarchy in action tests.
- Framework contract actions may use contract method names such as `create(...)`, `reset(...)`, or `update(...)` instead of `handle(...)`. Test those methods directly, including validation exceptions, validation bags, notifications, and persistence when the contract action owns them.

### Mocking Collaborators

Mock injected collaborators through the container:

```php
$this->mock(CodeGenerator::class, function (MockInterface $mock): void {
    $mock->shouldReceive('formattedId')
        ->once()
        ->with($expectedAlphabet, 6)
        ->andReturn('111111');
});
```

Assert retry behavior with `andReturnValues([...])` or `times(...)` when the action has retry limits.

### Generated Identifier Scope Examples

Generated identifier tests should mirror the exact uniqueness contract: owner scope, normalized query, inactive-state reuse or reservation, default soft-delete behavior, cross-owner reuse, and max-attempt exceptions.

```php
it('retries when an inactive child record already uses the normalized code', function (): void {
    $parentRecord = ParentRecord::factory()->createOne([
        'code_prefix' => 'HQ-',
    ]);

    ChildRecord::factory()
        ->for($parentRecord)
        ->inactive()
        ->createOne([
            'code' => 'HQ-004992',
            'normalized_code' => 'HQ004992',
        ]);

    $this->mock(CodeGenerator::class, function (MockInterface $mock): void {
        $mock->shouldReceive('formattedId')
            ->andReturnValues(['004992', '004993']);
    });

    $code = resolve(GenerateChildRecordCode::class)->handle($parentRecord);

    expect($code)->toBe('HQ-004993');
});

it('ignores soft deleted child records when checking generated codes', function (): void {
    $parentRecord = ParentRecord::factory()->createOne([
        'code_prefix' => 'HQ-',
    ]);

    ChildRecord::factory()
        ->for($parentRecord)
        ->trashed()
        ->createOne([
            'code' => 'HQ-004992',
            'normalized_code' => 'HQ004992',
        ]);

    $this->mock(CodeGenerator::class, function (MockInterface $mock): void {
        $mock->shouldReceive('formattedId')
            ->once()
            ->andReturn('004992');
    });

    $code = resolve(GenerateChildRecordCode::class)->handle($parentRecord);

    expect($code)->toBe('HQ-004992');
});

it('ignores matching normalized codes from another Workspace', function (): void {
    $parentRecord = ParentRecord::factory()->createOne([
        'code_prefix' => 'HQ-',
    ]);

    ChildRecord::factory()->createOne([
        'code' => 'HQ-004992',
        'normalized_code' => 'HQ004992',
    ]);

    $this->mock(CodeGenerator::class, function (MockInterface $mock): void {
        $mock->shouldReceive('formattedId')
            ->once()
            ->andReturn('004992');
    });

    $code = resolve(GenerateChildRecordCode::class)->handle($parentRecord);

    expect($code)->toBe('HQ-004992');
});
```

### Data Input Actions

Use this pattern when an action takes a Spatie Laravel Data input instead of a raw array:

```php
$updatedParentRecord = resolve(UpdateParentRecord::class)->handle(
    parentRecord: $parentRecord,
    input: UpdateParentRecordInput::from([
        'name' => 'Updated Parent',
        'enabled' => false,
    ]),
);

expect($updatedParentRecord->is($parentRecord))->toBeTrue();

assertDatabaseHas(ParentRecord::class, [
    'id' => $parentRecord->id,
    'name' => 'Updated Parent',
    'enabled' => false,
]);
```

For create inputs, include a focused default/required-only case only when omitted `Optional` fields should fall through to model `$attributes` defaults or the action otherwise owns omission behavior. Do not add a required-only create test just because another action has one, and do not put that test in a controller feature test unless the controller boundary itself handles omission. For update inputs, cover full updates, partial updates where omitted fields remain unchanged, and nullable fields clearing with explicit `null`.

```php
it('creates a child record', function (): void {
    $parentRecord = ParentRecord::factory()->createOne();

    $childRecord = resolve(CreateChildRecord::class)->handle(
        $parentRecord,
        CreateChildRecordInput::from([
            'description' => 'Primary child record',
            'name' => 'Primary Child',
        ]),
    );

    assertDatabaseHas(ChildRecord::class, [
        'id' => $childRecord->id,
        'parent_record_id' => $parentRecord->id,
        'description' => 'Primary child record',
        'name' => 'Primary Child',
    ]);
});

it('creates a parent record with only required fields', function (): void {
    $workspace = Workspace::factory()->createOne();

    $parentRecord = resolve(CreateParentRecord::class)->handle(
        $workspace,
        CreateParentRecordInput::from([
            'name' => 'Required Parent',
            'status' => ParentRecordStatus::Active(),
        ]),
    );

    assertDatabaseHas(ParentRecord::class, [
        'id' => $parentRecord->id,
        'workspace_id' => $workspace->id,
        'description' => null,
        'name' => 'Required Parent',
        'status' => ParentRecordStatus::Active,
    ]);
});
```

### Action-Owned Guard Examples

Use these examples as a catalog. A single action does not need every case, but the action test must include every compatible pattern owned by that action.

#### Top-Level Update

Test the persisted behavior owned by the action:

```php
it('updates only provided parent record fields', function (): void {
    $parentRecord = ParentRecord::factory()->createOne([
        'description' => 'Original description',
        'name' => 'Original Parent',
    ]);

    resolve(UpdateParentRecord::class)->handle(
        $parentRecord,
        UpdateParentRecordInput::from(['name' => 'Updated Parent']),
    );

    assertDatabaseHas(ParentRecord::class, [
        'id' => $parentRecord->id,
        'description' => 'Original description',
        'name' => 'Updated Parent',
    ]);
});

it('clears nullable parent record fields', function (): void {
    $parentRecord = ParentRecord::factory()->createOne([
        'description' => 'Original description',
    ]);

    resolve(UpdateParentRecord::class)->handle(
        $parentRecord,
        UpdateParentRecordInput::from(['description' => null]),
    );

    assertDatabaseHas(ParentRecord::class, [
        'id' => $parentRecord->id,
        'description' => null,
    ]);
});
```

#### Lifecycle Mutation

Lifecycle actions should prove their state transition directly:

```php
it('reactivates a parent record', function (): void {
    $parentRecord = ParentRecord::factory()->inactive()->createOne();

    resolve(ReactivateParentRecord::class)->handle($parentRecord);

    assertDatabaseHas(ParentRecord::class, [
        'id' => $parentRecord->id,
        'deactivated_at' => null,
    ]);
});
```

#### Scoped Bulk Update

For an action that updates siblings in the same scope, test the scope behavior and back uniqueness with a database constraint:

```php
it('only clears default child records for the same parent record', function (): void {
    $parentRecord = ParentRecord::factory()->createOne();
    $otherParentRecord = ParentRecord::factory()->createOne();

    $defaultChildRecord = ChildRecord::factory()->for($parentRecord)->default()->createOne();
    $otherDefaultChildRecord = ChildRecord::factory()->for($otherParentRecord)->default()->createOne();
    $newDefaultChildRecord = ChildRecord::factory()->for($parentRecord)->createOne(['is_default' => false]);

    resolve(SetDefaultChildRecord::class)->handle($newDefaultChildRecord);

    $defaultChildRecord->refresh();
    $otherDefaultChildRecord->refresh();
    $newDefaultChildRecord->refresh();

    expect($defaultChildRecord->is_default)->toBeFalse()
        ->and($otherDefaultChildRecord->is_default)->toBeTrue()
        ->and($newDefaultChildRecord->is_default)->toBeTrue();
});
```

#### Dependent-Record Guards

Dependent-record guards should include the variants that the action contract distinguishes. If active dependents block the action but inactive or soft-deleted dependents are ignored, show that explicitly:

```php
it('rejects parent records with active child records', function (): void {
    $parentRecord = ParentRecord::factory()->createOne();

    ChildRecord::factory()
        ->for($parentRecord)
        ->createOne();

    expect(fn () => resolve(DeactivateParentRecord::class)->handle($parentRecord))
        ->toThrow(
            CannotDeactivateParentRecord::class,
            'Cannot deactivate a parent record with active child records.',
        );

    assertDatabaseHas(ParentRecord::class, [
        'id' => $parentRecord->id,
        'deactivated_at' => null,
    ]);
});

it('deactivates when related child records are inactive', function (): void {
    $parentRecord = ParentRecord::factory()->createOne();

    ChildRecord::factory()
        ->inactive()
        ->for($parentRecord)
        ->createOne();

    resolve(DeactivateParentRecord::class)->handle($parentRecord);

    assertDatabaseHas(ParentRecord::class, [
        'id' => $parentRecord->id,
        'deactivated_at' => now(),
    ]);
});

it('deactivates when related child records are soft deleted', function (): void {
    $parentRecord = ParentRecord::factory()->createOne();

    ChildRecord::factory()
        ->trashed()
        ->for($parentRecord)
        ->createOne();

    resolve(DeactivateParentRecord::class)->handle($parentRecord);

    assertDatabaseHas(ParentRecord::class, [
        'id' => $parentRecord->id,
        'deactivated_at' => now(),
    ]);
});
```

If the action intentionally checks dependents with `withTrashed()`, soft-deleted dependents should still block and the parent should remain unchanged:

```php
it('rejects parent records with soft deleted child records when deletion checks all dependents', function (): void {
    $childRecord = ChildRecord::factory()->trashed()->createOne();

    expect(fn () => resolve(DeleteParentRecord::class)->handle($childRecord->parentRecord))
        ->toThrow(
            CannotDeleteParentRecord::class,
            'Cannot delete a parent record with dependent child records.',
        );

    assertNotSoftDeleted($childRecord->parentRecord);
});
```

#### Branch-Specific Domain Exceptions

When the action throws a domain exception, assert the exception class and exact message with `toThrow(Class::class, message)`. If the exception also carries a validation field for controller mapping, do not switch the action test to a closure just to inspect that field. The controller feature test should mock that same exception factory and assert the field-to-message mapping with `assertRedirectBackWithErrors(...)`.

```php
it('rejects overlapping ranges', function (): void {
    $childRecord = ChildRecord::factory()->createOne();

    LeafRecord::factory()
        ->for($childRecord)
        ->forRange(0, 2)
        ->createOne();

    expect(fn () => resolve(CreateLeafRecord::class)->handle(
        $childRecord,
        CreateLeafRecordInput::from([
            'maximum_value' => '3',
            'minimum_value' => '1',
            'name' => 'Standard Leaf',
            'amount' => '12.50',
        ]),
    ))->toThrow(
        CannotCreateLeafRecord::class,
        'The range overlaps an existing record.',
    );

    assertDatabaseMissing(LeafRecord::class, [
        'child_record_id' => $childRecord->id,
        'name' => 'Standard Leaf',
    ]);
});
```

#### Range Guards

For range-style create actions, cover same-scope failures and success variants:

```php
it('rejects overlapping ranges', function (): void {
    $childRecord = ChildRecord::factory()->createOne();

    LeafRecord::factory()
        ->for($childRecord)
        ->forRange(0, 2)
        ->createOne();

    expect(fn () => resolve(CreateLeafRecord::class)->handle(
        $childRecord,
        CreateLeafRecordInput::from([
            'maximum_value' => '3',
            'minimum_value' => '1',
            'name' => 'Standard Leaf',
            'amount' => '12.50',
        ]),
    ))->toThrow(
        CannotCreateLeafRecord::class,
        'The range overlaps an existing record.',
    );

    assertDatabaseMissing(LeafRecord::class, [
        'child_record_id' => $childRecord->id,
        'name' => 'Standard Leaf',
    ]);
});

it('rejects a second open-ended range', function (): void {
    $childRecord = ChildRecord::factory()->createOne();

    LeafRecord::factory()
        ->for($childRecord)
        ->forRange(5, null)
        ->createOne();

    expect(fn () => resolve(CreateLeafRecord::class)->handle(
        $childRecord,
        CreateLeafRecordInput::from([
            'maximum_value' => null,
            'minimum_value' => '10',
            'name' => 'Open Ended Leaf',
            'amount' => '12.50',
        ]),
    ))->toThrow(
        CannotCreateLeafRecord::class,
        'Only one open-ended range is allowed per child record.',
    );
});

it('creates adjacent ranges within the same child record', function (): void {
    $childRecord = ChildRecord::factory()->createOne();

    LeafRecord::factory()
        ->for($childRecord)
        ->forRange(0, 1)
        ->createOne();

    resolve(CreateLeafRecord::class)->handle(
        $childRecord,
        CreateLeafRecordInput::from([
            'maximum_value' => '5',
            'minimum_value' => '1',
            'name' => 'Adjacent Leaf',
            'amount' => '12.50',
        ]),
    );

    resolve(CreateLeafRecord::class)->handle(
        $childRecord,
        CreateLeafRecordInput::from([
            'maximum_value' => null,
            'minimum_value' => '5',
            'name' => 'Open Ended Leaf',
            'amount' => '12.50',
        ]),
    );

    expect($childRecord->leafRecords()->count())->toBe(3);
});

it('ignores soft deleted records when creating ranges', function (): void {
    $childRecord = ChildRecord::factory()->createOne();
    $leafRecord = LeafRecord::factory()
        ->for($childRecord)
        ->forRange(0, 2)
        ->createOne();

    $leafRecord->delete();

    resolve(CreateLeafRecord::class)->handle(
        $childRecord,
        CreateLeafRecordInput::from([
            'maximum_value' => '3',
            'minimum_value' => '1',
            'name' => 'Replacement Leaf',
            'amount' => '12.50',
        ]),
    );

    assertDatabaseHas(LeafRecord::class, [
        'child_record_id' => $childRecord->id,
        'maximum_value' => '3.0000',
        'minimum_value' => '1.0000',
    ]);

    assertSoftDeleted($leafRecord);
});

it('creates the same range in a different child record', function (): void {
    $childRecord = ChildRecord::factory()->createOne();
    $otherChildRecord = ChildRecord::factory()
        ->for($childRecord->parentRecord)
        ->createOne();

    LeafRecord::factory()
        ->for($childRecord)
        ->forRange(0, 2)
        ->createOne();

    resolve(CreateLeafRecord::class)->handle(
        $otherChildRecord,
        CreateLeafRecordInput::from([
            'maximum_value' => '2',
            'minimum_value' => '0',
            'name' => 'Other Leaf',
            'amount' => '12.50',
        ]),
    );

    assertDatabaseHas(LeafRecord::class, [
        'child_record_id' => $otherChildRecord->id,
        'maximum_value' => '2.0000',
        'minimum_value' => '0.0000',
    ]);
});

it('recreates a range after soft delete', function (): void {
    $leafRecord = LeafRecord::factory()
        ->forRange(5, null)
        ->createOne();
    $childRecord = $leafRecord->childRecord;

    $leafRecord->delete();

    resolve(CreateLeafRecord::class)->handle(
        $childRecord,
        CreateLeafRecordInput::from([
            'maximum_value' => null,
            'minimum_value' => '5',
            'name' => 'Replacement Open Ended Leaf',
            'amount' => '12.50',
        ]),
    );

    assertDatabaseHas(LeafRecord::class, [
        'child_record_id' => $childRecord->id,
        'maximum_value' => null,
        'minimum_value' => '5.0000',
    ]);

    assertSoftDeleted($leafRecord);
});
```

#### Owner Lifecycle Guards For Nested Mutations

When a create, update, or delete action re-checks an owner lifecycle state under a transaction, cover that guard in the action suite for each mutation shape the action owns. The controller still covers authorization and exception-to-validation mapping.

```php
it('rejects creating a leaf record when the owner is inactive', function (): void {
    $childRecord = ChildRecord::factory()
        ->for(ParentRecord::factory()->inactive())
        ->createOne();

    expect(fn () => resolve(CreateLeafRecord::class)->handle(
        $childRecord,
        CreateLeafRecordInput::from([
            'maximum_value' => '5',
            'minimum_value' => '0',
            'name' => 'Standard Leaf',
            'amount' => '12.50',
        ]),
    ))->toThrow(
        CannotCreateLeafRecord::class,
        'Cannot use an inactive parent record.',
    );

    assertDatabaseMissing(LeafRecord::class, [
        'child_record_id' => $childRecord->id,
        'name' => 'Standard Leaf',
    ]);
});

it('rejects updating a leaf record when the owner is inactive', function (): void {
    $leafRecord = LeafRecord::factory()
        ->for(ChildRecord::factory()->for(ParentRecord::factory()->inactive()))
        ->createOne(['name' => 'Original Leaf']);

    expect(fn () => resolve(UpdateLeafRecord::class)->handle(
        $leafRecord,
        UpdateLeafRecordInput::from(['name' => 'Updated Leaf']),
    ))->toThrow(
        CannotUpdateLeafRecord::class,
        'Cannot use an inactive parent record.',
    );

    assertDatabaseHas(LeafRecord::class, [
        'id' => $leafRecord->id,
        'name' => 'Original Leaf',
    ]);
});

it('rejects deleting a leaf record when the owner is inactive', function (): void {
    $leafRecord = LeafRecord::factory()
        ->for(ChildRecord::factory()->for(ParentRecord::factory()->inactive()))
        ->createOne();

    expect(fn () => resolve(DeleteLeafRecord::class)->handle(
        $leafRecord,
    ))->toThrow(
        CannotDeleteLeafRecord::class,
        'Cannot use an inactive parent record.',
    );

    assertNotSoftDeleted($leafRecord);
});
```

#### Model-Targeted Mutations

Update and delete actions receive only the target model and independent business inputs. Test their persistence and business guards here; keep ownership, route hierarchy, authorization, and soft-delete binding coverage at the entrypoint.

Do not assert deletion in delegated controller tests when the action owns the mutation.

```php
it('deletes a leaf record for an active owner', function (): void {
    $leafRecord = LeafRecord::factory()->createOne();

    resolve(DeleteLeafRecord::class)->handle($leafRecord);

    assertSoftDeleted($leafRecord);
});
```

#### Range Update Guards

For range-style update actions, cover same-scope failures, current-record exclusion, nullable clearing, and stored open-ended values when the action owns the range comparison.

```php
it('rejects overlapping ranges when updating a leaf record', function (): void {
    $leafRecord = LeafRecord::factory()
        ->forRange(3, 5)
        ->createOne(['name' => 'Original Leaf']);

    LeafRecord::factory()
        ->recycle($leafRecord->childRecord)
        ->forRange(0, 2)
        ->createOne();

    expect(fn () => resolve(UpdateLeafRecord::class)->handle(
        $leafRecord,
        UpdateLeafRecordInput::from([
            'maximum_value' => '4',
            'minimum_value' => '1',
        ]),
    ))->toThrow(
        CannotUpdateLeafRecord::class,
        'The range overlaps an existing record.',
    );

    assertDatabaseHas(LeafRecord::class, [
        'id' => $leafRecord->id,
        'maximum_value' => '5.0000',
        'minimum_value' => '3.0000',
        'name' => 'Original Leaf',
    ]);
});

it('rejects updating a leaf record to a second open-ended range', function (): void {
    $leafRecord = LeafRecord::factory()
        ->forRange(0, 5)
        ->createOne();

    LeafRecord::factory()
        ->recycle($leafRecord->childRecord)
        ->forRange(10, null)
        ->createOne();

    expect(fn () => resolve(UpdateLeafRecord::class)->handle(
        $leafRecord,
        UpdateLeafRecordInput::from(['maximum_value' => null]),
    ))->toThrow(
        CannotUpdateLeafRecord::class,
        'Only one open-ended range is allowed per child record.',
    );

    assertDatabaseHas(LeafRecord::class, [
        'id' => $leafRecord->id,
        'maximum_value' => '5.0000',
    ]);
});

it('excludes the updated leaf record from overlap validation', function (): void {
    $leafRecord = LeafRecord::factory()
        ->forRange(0, 2)
        ->createOne(['name' => 'Original Leaf']);

    resolve(UpdateLeafRecord::class)->handle(
        $leafRecord,
        UpdateLeafRecordInput::from(['name' => 'Updated without range conflict']),
    );

    assertDatabaseHas(LeafRecord::class, [
        'id' => $leafRecord->id,
        'maximum_value' => '2.0000',
        'minimum_value' => '0.0000',
        'name' => 'Updated without range conflict',
    ]);
});

it('clears nullable range fields', function (): void {
    $leafRecord = LeafRecord::factory()
        ->forRange(2, 10)
        ->createOne();

    resolve(UpdateLeafRecord::class)->handle(
        $leafRecord,
        UpdateLeafRecordInput::from(['maximum_value' => null]),
    );

    assertDatabaseHas(LeafRecord::class, [
        'id' => $leafRecord->id,
        'maximum_value' => null,
    ]);
});

it('allows minimum value updates when the stored maximum value is open ended', function (): void {
    $leafRecord = LeafRecord::factory()
        ->forRange(5, null)
        ->createOne();

    resolve(UpdateLeafRecord::class)->handle(
        $leafRecord,
        UpdateLeafRecordInput::from(['minimum_value' => '11']),
    );

    assertDatabaseHas(LeafRecord::class, [
        'id' => $leafRecord->id,
        'maximum_value' => null,
        'minimum_value' => '11.0000',
    ]);
});
```

### Framework Contract Actions

When an action implements a framework contract, call the contract method directly instead of forcing a `handle(...)` shape. Cover validation exceptions, validation bags, notifications, and persistence only when that contract action owns them:

```php
it('validates actor profile fields', function (array $data, array $expected): void {
    $actor = Actor::factory()->createOne();

    expect(fn () => resolve(UpdateActorProfileInformation::class)->update($actor, $data))
        ->toThrow(function (ValidationException $exception) use ($expected): void {
            expect($exception->validator->errors()->messages())->toBe($expected);
        });
})->with([
    'required' => [
        'data' => [
            'email' => '',
            'first_name' => '',
            'last_name' => '',
        ],
        'expected' => [
            'email' => ['The email field is required.'],
            'first_name' => ['The first name field is required.'],
            'last_name' => ['The last name field is required.'],
        ],
    ],
]);
```

## Coverage Expectations

For generator or coordinator actions, cover:

- the main success case;
- cleanup of previous active records;
- retry until a valid result exists;
- rejection of already-used or invalid candidates;
- owner-scoped uniqueness, normalized-code collisions, inactive-state reservation when inactive rows still count, default soft-delete reuse when soft-deleted rows are ignored, and cross-owner reuse when the value is owner-scoped;
- exception behavior at max attempts;
- exact exception message when the exception is part of the contract.
- Read the live action and sibling action tests before adding adjacent coverage; do not add symmetry tests when the action does not own that behavior.
- When an action chooses active records with `whereNull('deactivated_at')` or default soft-delete scopes, cover ignored deactivated and soft-deleted candidates when that branch affects which record is returned, promoted, or created.
- For Data input actions, cover the input behavior owned by the action boundary: full persistence, `Optional` omission, explicit `null` clearing, returned model identity when relevant, and owned side effects such as membership attachment or current-`Workspace` switching.
- Cover `Workspace` or parent isolation only when the parent is an independent business input, such as create-under-parent actions. Route ownership belongs to the entrypoint.
- When a controller catches a domain exception from the action and maps it to a validation error, the action integration test should prove the exception/guard and the controller feature test should mock that exception path. Do not duplicate the same action guard as a Form Request rule unless the request owns that validation before the action runs.
- Range-overlap guards, dependent-record delete guards, and lifecycle checks belong here when the action owns them. Back invariants with database constraints when PostgreSQL can express them, and cover same-scope failures, cross-scope success, soft-deleted reuse, adjacent/open-ended variants, active and soft-deleted dependency variants, and exact domain exceptions according to the action's contract.
- If a guard was intentionally moved from a Form Request to an action because it needs fresh database state or dependency checks, the action integration test becomes the source of truth for that guard. The controller feature test still remains as entry-point coverage for request validation, action invocation, redirects/toasts, and exception-to-validation mapping.

## Do Not

- Do not test action behavior only through a controller if the action has meaningful branching.
- Do not make live external calls; use mocks or HTTP fakes.
- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/Actions/README.md`
- `references/app/Exceptions/README.md`
