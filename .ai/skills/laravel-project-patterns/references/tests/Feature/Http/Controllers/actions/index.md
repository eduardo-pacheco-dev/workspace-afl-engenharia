# Index Action Templates

## Purpose

This reference defines `describe('index')` patterns for controller feature tests.

## When To Use

Use this reference when a web/session controller exposes an index/list page. For JSON list endpoints, keep the same scoping matrix and adapt assertions with `../modes/api-json.md`.

## Required Pattern

Actor context:

- `assertForbidden()` tests use an authenticated actor whose request resolves bindings but is not authorized for the route `Workspace`.
- List/success tests use an actor authorized for the route `Workspace`.
- `assertNotFound()` binding tests can use any authenticated actor unless the route uses a policy-masked `404`.

For three- and four-resource chains, prepend the full collection binding order from `../route-patterns.md`: authentication, unrelated Workspace authorization, outer parent wrong Workspace, outer parent soft-deleted, child wrong parent in the same Workspace, child wrong Workspace, child soft-deleted, then lifecycle/list contract.

### Two-Resource Route Chain (`workspaces.parent-records.index`)

```php
describe('index', function (): void {
    it('requires authentication', function (): void {
        $workspace = Workspace::factory()->createOne();

        $response = get(route('workspaces.parent-records.index', [
            'workspace' => $workspace,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents listing from an unrelated Workspace', function (): void {
        $workspace = Workspace::factory()->createOne();

        login();

        $response = get(route('workspaces.parent-records.index', [
            'workspace' => $workspace,
        ]));

        $response->assertForbidden();
    });

    it('lists parent records', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        login(workspace: $parentRecord->workspace);

        $response = get(route('workspaces.parent-records.index', [
            'workspace' => $parentRecord->workspace,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($parentRecord): void {
                $page->component('parent-records/Index')
                    ->where('workspace.id', $parentRecord->workspace->public_id)
                    ->where('parentRecords.data.0.id', $parentRecord->public_id);
            });
    });

    it('does not include parent records from other Workspaces', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();
        $otherParentRecord = ParentRecord::factory()->createOne();

        login(workspace: $parentRecord->workspace);

        $response = get(route('workspaces.parent-records.index', [
            'workspace' => $parentRecord->workspace,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($parentRecord, $otherParentRecord): void {
                $page->component('parent-records/Index')
                    ->has('parentRecords.data', 1, function (AssertableJson $json) use ($parentRecord, $otherParentRecord): void {
                        $json
                            ->where('id', $parentRecord->public_id)
                            ->whereNot('id', $otherParentRecord->public_id)
                            ->etc();
                    });
            });
    });
});
```

### Three-Resource Route Chain (`workspaces.parent-records.children.index`)

```php
describe('index', function (): void {
    it('requires authentication', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        $response = get(route('workspaces.parent-records.children.index', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents listing from an unrelated Workspace', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        login();

        $response = get(route('workspaces.parent-records.children.index', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertForbidden();
    });

    it('returns not found when parent record belongs to another Workspace', function (): void {
        $workspace = Workspace::factory()->createOne();
        $parentRecord = ParentRecord::factory()->createOne();

        login(workspace: $workspace);

        $response = get(route('workspaces.parent-records.children.index', [
            'workspace' => $workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when parent record is soft deleted', function (): void {
        $parentRecord = ParentRecord::factory()->trashed()->createOne();

        login(workspace: $parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.index', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertNotFound();
    });

    it('lists child records from the parent record', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.index', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($childRecord): void {
                $page->component('children/Index')
                    ->where('workspace.id', $childRecord->parentRecord->workspace->public_id)
                    ->where('parentRecord.id', $childRecord->parentRecord->public_id)
                    ->where('childRecords.data.0.id', $childRecord->public_id);
            });
    });

    it('does not include child records from another parent record', function (): void {
        $childRecord = ChildRecord::factory()->createOne();
        $otherChildRecord = ChildRecord::factory()
            ->recycle($childRecord->parentRecord->workspace)
            ->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.index', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($childRecord, $otherChildRecord): void {
                $page->component('children/Index')
                    ->has('childRecords.data', 1, function (AssertableJson $json) use ($childRecord, $otherChildRecord): void {
                        $json
                            ->where('id', $childRecord->public_id)
                            ->whereNot('id', $otherChildRecord->public_id)
                            ->etc();
                    });
            });
    });

    it('does not include child records from another Workspace', function (): void {
        $childRecord = ChildRecord::factory()->createOne();
        $otherChildRecord = ChildRecord::factory()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.index', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($childRecord, $otherChildRecord): void {
                $page->component('children/Index')
                    ->has('childRecords.data', 1, function (AssertableJson $json) use ($childRecord, $otherChildRecord): void {
                        $json
                            ->where('id', $childRecord->public_id)
                            ->whereNot('id', $otherChildRecord->public_id)
                            ->etc();
                    });
            });
    });

    it('does not include soft deleted child records', function (): void {
        $childRecord = ChildRecord::factory()->createOne();
        $softDeletedChildRecord = ChildRecord::factory()
            ->for($childRecord->parentRecord)
            ->trashed()
            ->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.index', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($childRecord, $softDeletedChildRecord): void {
                $page->component('children/Index')
                    ->has('childRecords.data', 1, function (AssertableJson $json) use ($childRecord, $softDeletedChildRecord): void {
                        $json
                            ->where('id', $childRecord->public_id)
                            ->whereNot('id', $softDeletedChildRecord->public_id)
                            ->etc();
                    });
            });
    });
});
```

### Four-Resource Route Chain (`workspaces.parent-records.children.leaves.index`)

```php
describe('index', function (): void {
    it('requires authentication', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        $response = get(route('workspaces.parent-records.children.leaves.index', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents listing from an unrelated Workspace', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login();

        $response = get(route('workspaces.parent-records.children.leaves.index', [
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

        $response = get(route('workspaces.parent-records.children.leaves.index', [
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

        $response = get(route('workspaces.parent-records.children.leaves.index', [
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

        $response = get(route('workspaces.parent-records.children.leaves.index', [
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

        $response = get(route('workspaces.parent-records.children.leaves.index', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when child record is soft deleted', function (): void {
        $childRecord = ChildRecord::factory()->trashed()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.leaves.index', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('lists leaf records from the child record', function (): void {
        $leafRecord = LeafRecord::factory()->createOne();

        login(workspace: $leafRecord->childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.leaves.index', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($leafRecord): void {
                $page->component('leaves/Index')
                    ->where('workspace.id', $leafRecord->childRecord->parentRecord->workspace->public_id)
                    ->where('parentRecord.id', $leafRecord->childRecord->parentRecord->public_id)
                    ->where('childRecord.id', $leafRecord->childRecord->public_id)
                    ->where('leafRecords.data.0.id', $leafRecord->public_id);
            });
    });

    it('lists leaf records when the parent record is inactive if read continuity is allowed', function (): void {
        $leafRecord = LeafRecord::factory()
            ->for(ChildRecord::factory()->for(ParentRecord::factory()->inactive()))
            ->createOne();

        login(workspace: $leafRecord->childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.leaves.index', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($leafRecord): void {
                $page->component('leaves/Index')
                    ->where('leafRecords.data.0.id', $leafRecord->public_id)
                    ->where('parentRecord.inactive_at', $leafRecord->childRecord->parentRecord->inactive_at->toJSON());
            });
    });

    it('does not include leaf records from another child record', function (): void {
        $leafRecord = LeafRecord::factory()->createOne();
        $otherLeafRecord = LeafRecord::factory()
            ->recycle($leafRecord->childRecord->parentRecord->workspace)
            ->createOne();

        login(workspace: $leafRecord->childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.leaves.index', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($leafRecord, $otherLeafRecord): void {
                $page->component('leaves/Index')
                    ->has('leafRecords.data', 1, function (AssertableJson $json) use ($leafRecord, $otherLeafRecord): void {
                        $json
                            ->where('id', $leafRecord->public_id)
                            ->whereNot('id', $otherLeafRecord->public_id)
                            ->etc();
                    });
            });
    });
});
```

### Exclusion Variants

Add list exclusion cases for every scope that can leak records:

- other Workspace;
- other direct parent in the same Workspace;
- deeper ancestor mismatch when the route has more than one parent;
- same direct parent with mismatched redundant `Workspace` or ancestor ownership;
- soft-deleted rows when the listed model uses soft deletes.

## Coverage Expectations

For nested index actions, include both successful listing context props and negative list-exclusion cases. The included collection count and excluded public IDs should be asserted in the same response contract when practical.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not drop applicable controller boundary coverage when adapting examples.
- Do not use real module, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
