# Destroy Action Templates

## Purpose

This reference defines `describe('destroy')` patterns for controller feature tests.

## When To Use

Use this reference when a web/session controller exposes a `destroy` action. For delegated destroy actions, keep controller tests at the HTTP boundary and move persistence/transactions to action integration tests.

## Required Pattern

Actor context:

- `assertForbidden()` tests use an authenticated actor whose request resolves bindings but is not authorized for the route `Workspace`.
- Success tests use an actor authorized for the route `Workspace`.
- `assertNotFound()` binding tests can use any authenticated actor unless the route uses a policy-masked `404`.

For three- and four-resource chains, prepend the full member binding order from `../route-patterns.md`: authentication, unrelated Workspace authorization, ancestor and parent `404` cases, child `404` cases, leaf wrong parent, leaf wrong ancestor graph, leaf wrong Workspace, leaf soft-deleted, then lifecycle/delete guard or success.

If deletion has domain preconditions, place those tests after binding/soft-delete coverage and before success.

### Two-Resource Route Chain (`workspaces.parent-records.destroy`)

```php
describe('destroy', function (): void {
    it('requires authentication', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        $response = delete(route('workspaces.parent-records.destroy', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents deleting from an unrelated Workspace', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        login();

        $response = delete(route('workspaces.parent-records.destroy', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertForbidden();
    });

    it('returns not found when parent record belongs to another Workspace', function (): void {
        $workspace = Workspace::factory()->createOne();
        $parentRecord = ParentRecord::factory()->createOne();

        login(workspace: $workspace);

        $response = delete(route('workspaces.parent-records.destroy', [
            'workspace' => $workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertNotFound();
    });

    it('deletes a parent record', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        login(workspace: $parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.destroy', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertRedirectToRoute('workspaces.parent-records.index', [
            'workspace' => $parentRecord->workspace,
        ])
            ->assertToast('Parent record deleted');

        assertSoftDeleted($parentRecord);
    });
});
```

### Three-Resource Route Chain (`workspaces.parent-records.children.destroy`)

```php
describe('destroy', function (): void {
    it('requires authentication', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        $response = delete(route('workspaces.parent-records.children.destroy', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents deleting from an unrelated Workspace', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login();

        $response = delete(route('workspaces.parent-records.children.destroy', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertForbidden();
    });

    it('returns not found when parent record belongs to another Workspace', function (): void {
        $workspace = Workspace::factory()->createOne();
        $childRecord = ChildRecord::factory()->createOne();

        login(workspace: $workspace);

        $response = delete(route('workspaces.parent-records.children.destroy', [
            'workspace' => $workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when parent record is soft deleted', function (): void {
        $parentRecord = ParentRecord::factory()->trashed()->createOne();
        $childRecord = ChildRecord::factory()
            ->for($parentRecord)
            ->createOne();

        login(workspace: $parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.children.destroy', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when child record belongs to another parent record', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();
        $childRecord = ChildRecord::factory()
            ->recycle($parentRecord->workspace)
            ->createOne();

        login(workspace: $parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.children.destroy', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when child record belongs to another Workspace', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();
        $childRecord = ChildRecord::factory()->createOne();

        login(workspace: $parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.children.destroy', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when child record is soft deleted', function (): void {
        $childRecord = ChildRecord::factory()->trashed()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.children.destroy', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('deletes a child record', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.children.destroy', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertRedirectToRoute('workspaces.parent-records.show', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
        ])
            ->assertToast('Child record deleted');

        assertSoftDeleted($childRecord);
    });
});
```

### Four-Resource Route Chain (`workspaces.parent-records.children.leaves.destroy`)

```php
describe('destroy', function (): void {
    it('requires authentication', function (): void {
        $leafRecord = LeafRecord::factory()->createOne();

        $response = delete(route('workspaces.parent-records.children.leaves.destroy', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents deleting from an unrelated Workspace', function (): void {
        $leafRecord = LeafRecord::factory()->createOne();

        login();

        $response = delete(route('workspaces.parent-records.children.leaves.destroy', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertForbidden();
    });

    it('returns not found when parent record belongs to another Workspace', function (): void {
        $workspace = Workspace::factory()->createOne();
        $leafRecord = LeafRecord::factory()->createOne();

        login(workspace: $workspace);

        $response = delete(route('workspaces.parent-records.children.leaves.destroy', [
            'workspace' => $workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when parent record is soft deleted', function (): void {
        $parentRecord = ParentRecord::factory()->trashed()->createOne();
        $leafRecord = LeafRecord::factory()
            ->for(ChildRecord::factory()->for($parentRecord))
            ->createOne();

        login(workspace: $parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.children.leaves.destroy', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when child record belongs to another parent record', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();
        $leafRecord = LeafRecord::factory()
            ->for(ChildRecord::factory()->recycle($parentRecord->workspace))
            ->createOne();

        login(workspace: $parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.children.leaves.destroy', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when child record belongs to another Workspace', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();
        $leafRecord = LeafRecord::factory()->createOne();

        login(workspace: $parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.children.leaves.destroy', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when child record is soft deleted', function (): void {
        $childRecord = ChildRecord::factory()->trashed()->createOne();
        $leafRecord = LeafRecord::factory()
            ->for($childRecord)
            ->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.children.leaves.destroy', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when leaf record belongs to another child record', function (): void {
        $childRecord = ChildRecord::factory()->createOne();
        $leafRecord = LeafRecord::factory()
            ->recycle($childRecord->parentRecord->workspace)
            ->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.children.leaves.destroy', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when leaf record belongs to another Workspace', function (): void {
        $childRecord = ChildRecord::factory()->createOne();
        $leafRecord = LeafRecord::factory()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.children.leaves.destroy', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when leaf record is soft deleted', function (): void {
        $leafRecord = LeafRecord::factory()->trashed()->createOne();

        login(workspace: $leafRecord->childRecord->parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.children.leaves.destroy', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertNotFound();
    });

    it('prevents deleting when the parent record is inactive', function (): void {
        $leafRecord = LeafRecord::factory()
            ->for(ChildRecord::factory()->for(ParentRecord::factory()->inactive()))
            ->createOne();

        login(workspace: $leafRecord->childRecord->parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.children.leaves.destroy', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertForbidden();
    });

    it('deletes a leaf record', function (): void {
        $leafRecord = LeafRecord::factory()->createOne();

        login(workspace: $leafRecord->childRecord->parentRecord->workspace);

        $response = delete(route('workspaces.parent-records.children.leaves.destroy', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertRedirectToRoute('workspaces.parent-records.children.leaves.index', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
        ])
            ->assertToast('Leaf record deleted');

        assertSoftDeleted($leafRecord);
    });
});
```

### Delegated Destroy Domain Rejection

```php
it('prevents deleting when related records exist', function (): void {
    $parentRecord = ParentRecord::factory()->createOne();

    login(workspace: $parentRecord->workspace);

    mock(DeleteParentRecord::class)
        ->shouldReceive('handle')
        ->once()
        ->withArgs(fn (ParentRecord $argument): bool => $argument->is($parentRecord))
        ->andThrow(CannotDeleteParentRecord::becauseRelatedRecordsExist());

    $response = delete(route('workspaces.parent-records.destroy', [
        'workspace' => $parentRecord->workspace,
        'parent_record' => $parentRecord,
    ]));

    $response->assertRedirectBackWithErrors([
        'parent_record' => 'The parent record cannot be deleted while related records exist.',
    ]);
});
```

When dependency families are distinct route contracts, keep separate controller tests for each family even if the delegated action maps them to the same validation message. Preserve active and soft-deleted dependency variants when both protect the HTTP contract.

```php
it('prevents deleting when configuration records exist', function (): void {
    $configurationRecord = ConfigurationRecord::factory()->createOne();

    login(workspace: $configurationRecord->parentRecord->workspace);

    mock(DeleteParentRecord::class)
        ->shouldReceive('handle')
        ->once()
        ->withArgs(fn (ParentRecord $argument): bool => $argument->is($configurationRecord->parentRecord))
        ->andThrow(CannotDeleteParentRecord::becauseDependenciesExist());

    $response = delete(route('workspaces.parent-records.destroy', [
        'workspace' => $configurationRecord->parentRecord->workspace,
        'parent_record' => $configurationRecord->parentRecord,
    ]));

    $response->assertRedirectBackWithErrors([
        'parent_record' => 'The parent record has dependent records and cannot be deleted.',
    ]);
});
```

```php
it('prevents deleting when soft-deleted operational records exist', function (): void {
    $operationalRecord = OperationalRecord::factory()
        ->trashed()
        ->createOne();

    login(workspace: $operationalRecord->parentRecord->workspace);

    mock(DeleteParentRecord::class)
        ->shouldReceive('handle')
        ->once()
        ->withArgs(fn (ParentRecord $argument): bool => $argument->is($operationalRecord->parentRecord))
        ->andThrow(CannotDeleteParentRecord::becauseDependenciesExist());

    $response = delete(route('workspaces.parent-records.destroy', [
        'workspace' => $operationalRecord->parentRecord->workspace,
        'parent_record' => $operationalRecord->parentRecord,
    ]));

    $response->assertRedirectBackWithErrors([
        'parent_record' => 'The parent record has dependent records and cannot be deleted.',
    ]);
});
```

### System Destroy Patterns

- If destroy delegates to an action, mock the action and assert bound models, redirect/toast, and exception-to-validation mapping.
- Do not add deletion-state assertions to delegated controller tests; action integration tests own state, locks, transactions, and complete guard matrices.
- Keep concrete HTTP dependency-family cases when they protect distinct route contracts. Use minimal fixtures, mock the delegated action to throw, and assert the HTTP validation error.
- If policy or lifecycle state stops before the action, assert the response and that the action was not called.
- Non-delegated soft deletes use `assertSoftDeleted(...)`; hard deletes use `assertModelMissing(...)`.
- If deletion has side effects owned by the controller, assert them after redirect/toast.

## Coverage Expectations

Use the live controller, routes, policies, actions, and sibling tests to decide the complete destroy matrix. Domain failure cases should assert HTTP validation errors and should not delete the record.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not drop applicable controller boundary coverage when adapting examples.
- Do not use real module, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
