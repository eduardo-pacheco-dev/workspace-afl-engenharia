# Edit Action Templates

## Purpose

This reference defines `describe('edit')` patterns for controller feature tests.

## When To Use

Use this reference when a web/session controller exposes an edit page. For JSON endpoints, keep the same boundary order and adapt assertions with `../modes/api-json.md`.

## Required Pattern

Actor context:

- `assertForbidden()` tests use an authenticated actor whose request resolves bindings but is not authorized for the route `Workspace`.
- Success tests use an actor authorized for the route `Workspace`.
- `assertNotFound()` binding tests can use any authenticated actor unless the route uses a policy-masked `404`.

For three- and four-resource chains, prepend the full member binding order from `../route-patterns.md`: authentication, unrelated Workspace authorization, ancestor and parent `404` cases, child `404` cases, leaf wrong parent, leaf wrong ancestor graph, leaf wrong Workspace, leaf soft-deleted, then lifecycle/edit-page contract.

### Two-Resource Route Chain (`workspaces.parent-records.edit`)

```php
describe('edit', function (): void {
    it('requires authentication', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        $response = get(route('workspaces.parent-records.edit', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents viewing from an unrelated Workspace', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        login();

        $response = get(route('workspaces.parent-records.edit', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertForbidden();
    });

    it('returns not found when parent record belongs to another Workspace', function (): void {
        $workspace = Workspace::factory()->createOne();
        $parentRecord = ParentRecord::factory()->createOne();

        login(workspace: $workspace);

        $response = get(route('workspaces.parent-records.edit', [
            'workspace' => $workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertNotFound();
    });

    it('shows the edit parent record page', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        login(workspace: $parentRecord->workspace);

        $response = get(route('workspaces.parent-records.edit', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($parentRecord): void {
                $page->component('parent-records/Edit')
                    ->where('hasLeafRecords', false)
                    ->where('workspace.id', $parentRecord->workspace->public_id)
                    ->where('parentRecord.id', $parentRecord->public_id)
                    ->where('exampleModes', ExampleMode::options());
            });
    });
});
```

### Three-Resource Route Chain (`workspaces.parent-records.children.edit`)

```php
describe('edit', function (): void {
    it('requires authentication', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        $response = get(route('workspaces.parent-records.children.edit', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents viewing from an unrelated Workspace', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login();

        $response = get(route('workspaces.parent-records.children.edit', [
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

        $response = get(route('workspaces.parent-records.children.edit', [
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

        $response = get(route('workspaces.parent-records.children.edit', [
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

        $response = get(route('workspaces.parent-records.children.edit', [
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

        $response = get(route('workspaces.parent-records.children.edit', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when child record is soft deleted', function (): void {
        $childRecord = ChildRecord::factory()->trashed()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.edit', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('shows the edit child record page', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.edit', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($childRecord): void {
                $page->component('children/Edit')
                    ->where('workspace.id', $childRecord->parentRecord->workspace->public_id)
                    ->where('parentRecord.id', $childRecord->parentRecord->public_id)
                    ->where('childRecord.id', $childRecord->public_id)
                    ->where('exampleTypes', ExampleType::options());
            });
    });
});
```

### Four-Resource Route Chain (`workspaces.parent-records.children.leaves.edit`)

```php
describe('edit', function (): void {
    it('requires authentication', function (): void {
        $leafRecord = LeafRecord::factory()->createOne();

        $response = get(route('workspaces.parent-records.children.leaves.edit', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents viewing from an unrelated Workspace', function (): void {
        $leafRecord = LeafRecord::factory()->createOne();

        login();

        $response = get(route('workspaces.parent-records.children.leaves.edit', [
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

        $response = get(route('workspaces.parent-records.children.leaves.edit', [
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

        $response = get(route('workspaces.parent-records.children.leaves.edit', [
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

        $response = get(route('workspaces.parent-records.children.leaves.edit', [
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

        $response = get(route('workspaces.parent-records.children.leaves.edit', [
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

        $response = get(route('workspaces.parent-records.children.leaves.edit', [
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

        $response = get(route('workspaces.parent-records.children.leaves.edit', [
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

        $response = get(route('workspaces.parent-records.children.leaves.edit', [
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

        $response = get(route('workspaces.parent-records.children.leaves.edit', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertNotFound();
    });

    it('prevents viewing when the parent record is inactive', function (): void {
        $leafRecord = LeafRecord::factory()
            ->for(ChildRecord::factory()->for(ParentRecord::factory()->inactive()))
            ->createOne();

        login(workspace: $leafRecord->childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.leaves.edit', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertForbidden();
    });

    it('shows the edit leaf record page', function (): void {
        $leafRecord = LeafRecord::factory()->createOne();

        login(workspace: $leafRecord->childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.leaves.edit', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($leafRecord): void {
                $page->component('leaves/Edit')
                    ->where('workspace.id', $leafRecord->childRecord->parentRecord->workspace->public_id)
                    ->where('parentRecord.id', $leafRecord->childRecord->parentRecord->public_id)
                    ->where('childRecord.id', $leafRecord->childRecord->public_id)
                    ->where('leafRecord.id', $leafRecord->public_id);
            });
    });
});
```

### System Edit Patterns

- Edit pages with derived state must assert that prop in both false and true states when the form relies on it.
- Edit pages with enum or reference-data options assert those props along with every ancestor public ID.
- Edit pages with dependent options assert the stored value in the full response and `reloadOnly(...)` for refreshes.
- Deep edit pages follow the full leaf boundary order before lifecycle guards and the response contract.

## Coverage Expectations

Use the live controller, routes, form requests, policies, resources, and sibling tests to decide the complete matrix. Preserve examples, but keep them synthetic and only implement applicable cases in PHP.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not drop applicable controller boundary coverage when adapting examples.
- Do not use real module, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
