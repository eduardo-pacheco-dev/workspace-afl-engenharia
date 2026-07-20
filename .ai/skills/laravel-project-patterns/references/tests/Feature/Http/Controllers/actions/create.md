# Create Action Templates

## Purpose

This reference defines `describe('create')` patterns for controller feature tests.

## When To Use

Use this reference when a web/session controller exposes a `create` page. For JSON endpoints, keep the same boundary order and adapt assertions with `../modes/api-json.md`.

## Required Pattern

Use these templates as baselines and extend them with every boundary present in the real route, policy, controller, model, and sibling tests.

Actor context:

- `assertForbidden()` tests use an authenticated actor whose request resolves bindings but is not authorized for the route `Workspace`.
- Success tests use an actor authorized for the route `Workspace`.
- `assertNotFound()` binding tests can use any authenticated actor unless the route uses a policy-masked `404`.

For three- and four-resource chains, prepend the full collection binding order from `../route-patterns.md`: authentication, unrelated Workspace authorization, outer parent wrong Workspace, outer parent soft-deleted, child wrong parent in the same Workspace, child wrong Workspace, child soft-deleted, then lifecycle/create-page contract.

### Two-Resource Route Chain (`workspaces.parent-records.create`)

```php
describe('create', function (): void {
    it('requires authentication', function (): void {
        $workspace = Workspace::factory()->createOne();

        $response = get(route('workspaces.parent-records.create', [
            'workspace' => $workspace,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents viewing from an unrelated Workspace', function (): void {
        $workspace = Workspace::factory()->createOne();

        login();

        $response = get(route('workspaces.parent-records.create', [
            'workspace' => $workspace,
        ]));

        $response->assertForbidden();
    });

    it('shows the create parent record page', function (): void {
        $workspace = Workspace::factory()->createOne();

        login(workspace: $workspace);

        $response = get(route('workspaces.parent-records.create', [
            'workspace' => $workspace,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($workspace): void {
                $page->component('parent-records/Create')
                    ->where('workspace.id', $workspace->public_id)
                    ->where('exampleModes', ExampleMode::options());
            });
    });
});
```

### Three-Resource Route Chain (`workspaces.parent-records.children.create`)

```php
describe('create', function (): void {
    it('requires authentication', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        $response = get(route('workspaces.parent-records.children.create', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents viewing from an unrelated Workspace', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        login();

        $response = get(route('workspaces.parent-records.children.create', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertForbidden();
    });

    it('returns not found when parent record belongs to another Workspace', function (): void {
        $workspace = Workspace::factory()->createOne();
        $parentRecord = ParentRecord::factory()->createOne();

        login(workspace: $workspace);

        $response = get(route('workspaces.parent-records.children.create', [
            'workspace' => $workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when parent record is soft deleted', function (): void {
        $parentRecord = ParentRecord::factory()->trashed()->createOne();

        login(workspace: $parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.create', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertNotFound();
    });

    it('shows the create child record page', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        login(workspace: $parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.create', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($parentRecord): void {
                $page->component('children/Create')
                    ->where('workspace.id', $parentRecord->workspace->public_id)
                    ->where('parentRecord.id', $parentRecord->public_id)
                    ->where('exampleTypes', ExampleType::options());
            });
    });
});
```

### Four-Resource Route Chain (`workspaces.parent-records.children.leaves.create`)

```php
describe('create', function (): void {
    it('requires authentication', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        $response = get(route('workspaces.parent-records.children.leaves.create', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents viewing from an unrelated Workspace', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login();

        $response = get(route('workspaces.parent-records.children.leaves.create', [
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

        $response = get(route('workspaces.parent-records.children.leaves.create', [
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

        $response = get(route('workspaces.parent-records.children.leaves.create', [
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

        $response = get(route('workspaces.parent-records.children.leaves.create', [
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

        $response = get(route('workspaces.parent-records.children.leaves.create', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when child record is soft deleted', function (): void {
        $childRecord = ChildRecord::factory()->trashed()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.leaves.create', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('prevents viewing when the parent record is inactive', function (): void {
        $childRecord = ChildRecord::factory()
            ->for(ParentRecord::factory()->inactive())
            ->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.leaves.create', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertForbidden();
    });

    it('shows the create leaf record page', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = get(route('workspaces.parent-records.children.leaves.create', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($childRecord): void {
                $page->component('leaves/Create')
                    ->where('workspace.id', $childRecord->parentRecord->workspace->public_id)
                    ->where('parentRecord.id', $childRecord->parentRecord->public_id)
                    ->where('childRecord.id', $childRecord->public_id);
            });
    });
});
```

### Partial Reload Example

Use this when the page supports a dependent option reload. Keep the route context in the full response and assert only the refreshed prop in `reloadOnly(...)`.

```php
$response->assertOk()
    ->assertInertia(function (AssertableInertia $page) use ($workspace, $relatedRecord): void {
        $page->component('parent-records/Create')
            ->where('workspace.id', $workspace->public_id)
            ->where('relatedRecordId', $relatedRecord->public_id)
            ->reloadOnly('relatedOptions', function (AssertableInertia $reload) use ($relatedRecord): void {
                $reload->where('relatedOptions.0.value', $relatedRecord->public_id)
                    ->where('relatedOptions.0.label', $relatedRecord->name);
            });
    });
```

### System Create Patterns

- Create pages with enum or reference-data options assert those props in the primary page contract.
- Dynamic option pages assert `reloadOnly(...)` for the partial prop and the input that scopes it.
- Nested create pages assert every ancestor public ID prop used by the form.
- Deep create pages follow parent boundary order before lifecycle guards and the success response.

## Coverage Expectations

Use the live controller, routes, form requests, policies, resources, and sibling tests to decide the complete action matrix. Preserve examples, but keep them synthetic and only implement applicable cases in PHP.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not drop applicable controller boundary coverage when adapting examples.
- Do not use real module, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
