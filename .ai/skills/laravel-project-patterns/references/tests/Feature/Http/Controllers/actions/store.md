# Store Action Templates

## Purpose

This reference defines `describe('store')` patterns for controller feature tests.

## When To Use

Use this reference when a web/session controller exposes a `store` action. For JSON endpoints, keep the same validation and side-effect discipline and adapt assertions with `../modes/api-json.md`.

## Required Pattern

The store controller test always proves the HTTP entry point. If persistence is controller-owned, assert database effects here. If creation is delegated to a Data input-backed action, mock the action and assert request-to-input mapping, route-bound parent identity, redirect/toast, and exception-to-validation mapping. Persistence, defaults, generated values, transactions, locks, and domain guards belong in `tests/Integration/Actions`.

Actor context:

- `assertForbidden()` tests use an authenticated actor whose request resolves bindings but is not authorized for the route `Workspace`.
- Validation and success tests use an actor authorized for the route `Workspace`.
- `assertNotFound()` binding tests can use any authenticated actor unless the route uses a policy-masked `404`.

For three- and four-resource chains, prepend the full collection binding order from `../route-patterns.md`: authentication, unrelated Workspace authorization, outer parent wrong Workspace, outer parent soft-deleted, child wrong parent in the same Workspace, child wrong Workspace, child soft-deleted, then lifecycle, validation, mapped action exceptions, and success.

After the base validation dataset, add named validation tests for scoped uniqueness, parent-dependent `exists`, stored public-ID resolution, and request-owned cross-field rules. Put action-owned domain guards in the action suite and keep the controller test for mocked exception mapping.

### Two-Resource Route Chain (`workspaces.parent-records.store`)

```php
describe('store', function (): void {
    it('requires authentication', function (): void {
        $workspace = Workspace::factory()->createOne();

        $response = post(route('workspaces.parent-records.store', [
            'workspace' => $workspace,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents creating from an unrelated Workspace', function (): void {
        $workspace = Workspace::factory()->createOne();

        login();

        $response = post(route('workspaces.parent-records.store', [
            'workspace' => $workspace,
        ]));

        $response->assertForbidden();
    });

    it('validates fields', function (array $data, array $expected): void {
        $workspace = Workspace::factory()->createOne();

        login(workspace: $workspace);

        $response = post(route('workspaces.parent-records.store', [
            'workspace' => $workspace,
        ]), $data);

        $response->assertRedirectBackWithErrors($expected);
    })->with([
        'enum' => [
            'data' => [
                'example_mode' => 'invalid',
            ],
            'expected' => [
                'example_mode' => 'The selected example mode is invalid.',
            ],
        ],
        'max:255 (string)' => [
            'data' => [
                'name' => Str::repeat('a', 256),
            ],
            'expected' => [
                'name' => 'The name field must not be greater than 255 characters.',
            ],
        ],
        'required' => [
            'data' => [],
            'expected' => [
                'example_mode' => 'The example mode field is required.',
                'name' => 'The name field is required.',
            ],
        ],
    ]);

    it('creates a parent record', function (): void {
        $workspace = Workspace::factory()->createOne();

        login(workspace: $workspace);

        $response = post(route('workspaces.parent-records.store', [
            'workspace' => $workspace,
        ]), [
            'example_mode' => ExampleMode::Primary->value,
            'name' => 'Example Parent',
        ]);

        $parentRecord = $workspace->parentRecords()
            ->where('name', 'Example Parent')
            ->sole();

        $response->assertRedirectToRoute('workspaces.parent-records.show', [
            'workspace' => $workspace,
            'parent_record' => $parentRecord,
        ])
            ->assertToast('Parent record created');

        assertDatabaseHas(ParentRecord::class, [
            'workspace_id' => $workspace->id,
            'example_mode' => ExampleMode::Primary,
            'name' => 'Example Parent',
        ]);
    });
});
```

### Three-Resource Route Chain (`workspaces.parent-records.children.store`)

```php
describe('store', function (): void {
    it('requires authentication', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        $response = post(route('workspaces.parent-records.children.store', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents creating from an unrelated Workspace', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        login();

        $response = post(route('workspaces.parent-records.children.store', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertForbidden();
    });

    it('returns not found when parent record belongs to another Workspace', function (): void {
        $workspace = Workspace::factory()->createOne();
        $parentRecord = ParentRecord::factory()->createOne();

        login(workspace: $workspace);

        $response = post(route('workspaces.parent-records.children.store', [
            'workspace' => $workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when parent record is soft deleted', function (): void {
        $parentRecord = ParentRecord::factory()->trashed()->createOne();

        login(workspace: $parentRecord->workspace);

        $response = post(route('workspaces.parent-records.children.store', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertNotFound();
    });

    it('validates related record belongs to the parent record scope', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();
        $relatedRecord = RelatedRecord::factory()->createOne();

        login(workspace: $parentRecord->workspace);

        $response = post(route('workspaces.parent-records.children.store', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]), [
            'related_record_id' => $relatedRecord->public_id,
        ]);

        $response->assertRedirectBackWithErrors([
            'related_record_id' => 'The selected related record id is invalid.',
        ]);
    });

    it('creates a child record through the delegated action', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();
        $childRecord = ChildRecord::factory()
            ->for($parentRecord)
            ->createOne();

        login(workspace: $parentRecord->workspace);

        mock(CreateChildRecord::class)
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (
                ParentRecord $parentRecordArgument,
                ExampleInput $input
            ): bool => $parentRecordArgument->is($parentRecord)
                && $input->name === 'Example Child')
            ->andReturn($childRecord);

        $response = post(route('workspaces.parent-records.children.store', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]), [
            'name' => 'Example Child',
        ]);

        $response->assertRedirectToRoute('workspaces.parent-records.children.show', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
            'child_record' => $childRecord,
        ])
            ->assertToast('Child record created');
    });
});
```

### Four-Resource Route Chain (`workspaces.parent-records.children.leaves.store`)

```php
describe('store', function (): void {
    it('requires authentication', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        $response = post(route('workspaces.parent-records.children.leaves.store', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents creating from an unrelated Workspace', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login();

        $response = post(route('workspaces.parent-records.children.leaves.store', [
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

        $response = post(route('workspaces.parent-records.children.leaves.store', [
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

        $response = post(route('workspaces.parent-records.children.leaves.store', [
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

        $response = post(route('workspaces.parent-records.children.leaves.store', [
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

        $response = post(route('workspaces.parent-records.children.leaves.store', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when child record is soft deleted', function (): void {
        $childRecord = ChildRecord::factory()->trashed()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = post(route('workspaces.parent-records.children.leaves.store', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('does not call the action when request validation fails', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        mock(CreateLeafRecord::class)
            ->shouldNotReceive('handle');

        $response = post(route('workspaces.parent-records.children.leaves.store', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]), [
            'name' => '',
        ]);

        $response->assertRedirectBackWithErrors([
            'name' => 'The name field is required.',
        ]);
    });

    it('rejects overlapping ranges', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        mock(CreateLeafRecord::class)
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (
                ChildRecord $childRecordArgument,
                ExampleInput $input
            ): bool => $childRecordArgument->is($childRecord)
                && $input->name === 'Example Leaf')
            ->andThrow(CannotCreateLeafRecord::becauseRangeOverlaps());

        $response = post(route('workspaces.parent-records.children.leaves.store', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]), [
            'name' => 'Example Leaf',
        ]);

        $response->assertRedirectBackWithErrors([
            'range' => 'The requested range overlaps an existing record.',
        ]);
    });

    it('rejects a second open-ended range', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        mock(CreateLeafRecord::class)
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (
                ChildRecord $childRecordArgument,
                ExampleInput $input
            ): bool => $childRecordArgument->is($childRecord)
                && $input->maximumValue === null
                && $input->minimumValue === '10')
            ->andThrow(CannotCreateLeafRecord::becauseOpenEndedRangeAlreadyExists());

        $response = post(route('workspaces.parent-records.children.leaves.store', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]), [
            'minimum_value' => '10',
            'maximum_value' => null,
            'name' => 'Open Ended Leaf',
        ]);

        $response->assertRedirectBackWithErrors([
            'maximum_value' => 'Only one open-ended range is allowed.',
        ]);
    });

    it('does not evaluate action-owned range guards when request validation fails', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        mock(CreateLeafRecord::class)
            ->shouldNotReceive('handle');

        $response = post(route('workspaces.parent-records.children.leaves.store', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]), [
            'minimum_value' => '1',
            'maximum_value' => '3',
        ]);

        $response->assertRedirectBackWithErrors([
            'name' => 'The name field is required.',
        ])
            ->assertSessionDoesntHaveErrors(['minimum_value']);
    });

    it('creates a leaf record through the delegated action', function (): void {
        $childRecord = ChildRecord::factory()->createOne();
        $leafRecord = LeafRecord::factory()
            ->for($childRecord)
            ->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        mock(CreateLeafRecord::class)
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (
                ChildRecord $childRecordArgument,
                ExampleInput $input
            ): bool => $childRecordArgument->is($childRecord)
                && $input->name === 'Example Leaf')
            ->andReturn($leafRecord);

        $response = post(route('workspaces.parent-records.children.leaves.store', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]), [
            'name' => 'Example Leaf',
        ]);

        $response->assertRedirectToRoute('workspaces.parent-records.children.leaves.show', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
            'leaf_record' => $leafRecord,
        ])
            ->assertToast('Leaf record created');
    });

    it('creates a leaf record with an open-ended maximum value', function (): void {
        $childRecord = ChildRecord::factory()->createOne();
        $leafRecord = LeafRecord::factory()
            ->for($childRecord)
            ->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        mock(CreateLeafRecord::class)
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (
                ChildRecord $childRecordArgument,
                ExampleInput $input
            ): bool => $childRecordArgument->is($childRecord)
                && $input->maximumValue === null
                && $input->minimumValue === '0.5'
                && $input->name === 'Open Ended Leaf')
            ->andReturn($leafRecord);

        $response = post(route('workspaces.parent-records.children.leaves.store', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]), [
            'minimum_value' => '0.5',
            'maximum_value' => null,
            'name' => 'Open Ended Leaf',
        ]);

        $response->assertRedirectToRoute('workspaces.parent-records.children.leaves.show', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
            'leaf_record' => $leafRecord,
        ])
            ->assertToast('Leaf record created');
    });
});
```

### Additional Validation References

Load focused validation files before the broader catalog:

- `validation/required-with-and-array.md`
- `validation/scoped-exists-and-unique.md`
- `validation/prepare-for-validation.md`
- `validation/store-validates-fields.md`

### System Store Patterns

- Controller-owned store tests assert database persistence, normalized values, resolved public IDs, redirects, and toasts.
- Delegated store tests mock the action, assert parent/child identity and minimal input mapping, and return a persisted model only when needed for redirect route generation.
- For primary delegated success, a required-only payload may assert required input mapping. Optional/default behavior belongs in action integration tests unless the controller owns it.
- Lifecycle guards that stop before validation send minimal payloads and assert the action was not called.
- Server-managed fields stay out of request rules unless the endpoint intentionally exposes a `missing` or `prohibited` message.
- Scoped uniqueness covers same-scope failure, allowed cross-scope reuse, soft-deleted reuse when permitted, and inactive reserved rows when still counted.
- Range/overlap domains stay in controller validation only when the Form Request owns the rule. If the guard needs locked state or transactional reads, mock exception mapping here and put the complete matrix in action integration tests.
- For delegated range creation, keep controller examples for overlap exceptions, second open-ended range exceptions, invalid-base-validation short-circuiting before the action, and successful open-ended input mapping when the controller route needs those HTTP contracts.

## Coverage Expectations

Use the live controller, routes, form requests, policies, resources, actions, and sibling tests to decide the complete store matrix. Named tests are preferred for validation that needs persisted rows.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not drop applicable controller boundary coverage when adapting examples.
- Do not use real module, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
