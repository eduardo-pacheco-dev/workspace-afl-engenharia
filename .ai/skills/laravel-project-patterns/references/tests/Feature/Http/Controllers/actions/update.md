# Update Action Templates

## Purpose

This reference defines `describe('update')` patterns for controller feature tests.

## When To Use

Use this reference when a web/session controller exposes an `update` action. For JSON endpoints, keep the same validation and side-effect discipline and adapt assertions with `../modes/api-json.md`.

## Required Pattern

The update controller test always proves the HTTP entry point. If persistence is controller-owned, assert database effects here. If update is delegated to a Data input-backed action, mock the action and assert route-bound model identity, request-to-input mapping, redirect/toast, and exception-to-validation mapping. Persistence, `Optional`, nullable clearing, transactions, locks, and dependent-record guards belong in `tests/Integration/Actions`.

Actor context:

- `assertForbidden()` tests use an authenticated actor whose request resolves bindings but is not authorized for the route `Workspace`.
- Validation and success tests use an actor authorized for the route `Workspace`.
- `assertNotFound()` binding tests can use any authenticated actor unless the route uses a policy-masked `404`.

For three- and four-resource chains, prepend the full member binding order from `../route-patterns.md`: authentication, unrelated Workspace authorization, ancestor and parent `404` cases, child `404` cases, leaf wrong parent, leaf wrong ancestor graph, leaf wrong Workspace, leaf soft-deleted, then lifecycle, validation, mapped action exceptions, and success.

After the base validation dataset, add named tests for scoped uniqueness, stored-value comparisons, nullable relationship clearing, request-owned dependent-record rules, and mapped action exceptions.

### Two-Resource Route Chain (`workspaces.parent-records.update`)

```php
describe('update', function (): void {
    it('requires authentication', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        $response = patch(route('workspaces.parent-records.update', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents updating from an unrelated Workspace', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        login();

        $response = patch(route('workspaces.parent-records.update', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertForbidden();
    });

    it('returns not found when parent record belongs to another Workspace', function (): void {
        $workspace = Workspace::factory()->createOne();
        $parentRecord = ParentRecord::factory()->createOne();

        login(workspace: $workspace);

        $response = patch(route('workspaces.parent-records.update', [
            'workspace' => $workspace,
            'parent_record' => $parentRecord,
        ]));

        $response->assertNotFound();
    });

    it('validates fields', function (array $data, array $expected): void {
        $parentRecord = ParentRecord::factory()->createOne();

        login(workspace: $parentRecord->workspace);

        $response = patch(route('workspaces.parent-records.update', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
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
        'sometimes (required)' => [
            'data' => [
                'example_mode' => '',
                'name' => '',
            ],
            'expected' => [
                'example_mode' => 'The example mode field is required.',
                'name' => 'The name field is required.',
            ],
        ],
    ]);

    it('updates a parent record', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        login(workspace: $parentRecord->workspace);

        $response = patch(route('workspaces.parent-records.update', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]), [
            'example_mode' => ExampleMode::Secondary->value,
            'name' => 'Updated Parent',
        ]);

        $response->assertRedirectToRoute('workspaces.parent-records.show', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ])
            ->assertToast('Parent record updated');

        assertDatabaseHas(ParentRecord::class, [
            'id' => $parentRecord->id,
            'example_mode' => ExampleMode::Secondary,
            'name' => 'Updated Parent',
        ]);
    });
});
```

### Three-Resource Route Chain (`workspaces.parent-records.children.update`)

```php
describe('update', function (): void {
    it('requires authentication', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        $response = patch(route('workspaces.parent-records.children.update', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents updating from an unrelated Workspace', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login();

        $response = patch(route('workspaces.parent-records.children.update', [
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

        $response = patch(route('workspaces.parent-records.children.update', [
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

        $response = patch(route('workspaces.parent-records.children.update', [
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

        $response = patch(route('workspaces.parent-records.children.update', [
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

        $response = patch(route('workspaces.parent-records.children.update', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('returns not found when child record is soft deleted', function (): void {
        $childRecord = ChildRecord::factory()->trashed()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        $response = patch(route('workspaces.parent-records.children.update', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]));

        $response->assertNotFound();
    });

    it('passes partial input to the delegated action', function (): void {
        $childRecord = ChildRecord::factory()->createOne();

        login(workspace: $childRecord->parentRecord->workspace);

        mock(UpdateChildRecord::class)
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (
                ParentRecord $parentRecord,
                ChildRecord $childRecordArgument,
                ExampleInput $input
            ): bool => $parentRecord->is($childRecord->parentRecord)
                && $childRecordArgument->is($childRecord)
                && $input->name === 'Updated Child')
            ->andReturn($childRecord);

        $response = patch(route('workspaces.parent-records.children.update', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ]), [
            'name' => 'Updated Child',
        ]);

        $response->assertRedirectToRoute('workspaces.parent-records.children.show', [
            'workspace' => $childRecord->parentRecord->workspace,
            'parent_record' => $childRecord->parentRecord,
            'child_record' => $childRecord,
        ])
            ->assertToast('Child record updated');
    });
});
```

### Four-Resource Route Chain (`workspaces.parent-records.children.leaves.update`)

```php
describe('update', function (): void {
    it('requires authentication', function (): void {
        $leafRecord = LeafRecord::factory()->createOne();

        $response = patch(route('workspaces.parent-records.children.leaves.update', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertRedirectToRoute('login');
    });

    it('prevents updating from an unrelated Workspace', function (): void {
        $leafRecord = LeafRecord::factory()->createOne();

        login();

        $response = patch(route('workspaces.parent-records.children.leaves.update', [
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

        $response = patch(route('workspaces.parent-records.children.leaves.update', [
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

        $response = patch(route('workspaces.parent-records.children.leaves.update', [
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

        $response = patch(route('workspaces.parent-records.children.leaves.update', [
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

        $response = patch(route('workspaces.parent-records.children.leaves.update', [
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

        $response = patch(route('workspaces.parent-records.children.leaves.update', [
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

        $response = patch(route('workspaces.parent-records.children.leaves.update', [
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

        $response = patch(route('workspaces.parent-records.children.leaves.update', [
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

        $response = patch(route('workspaces.parent-records.children.leaves.update', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]));

        $response->assertNotFound();
    });

    it('does not call the action when request validation fails', function (): void {
        $leafRecord = LeafRecord::factory()->createOne();

        login(workspace: $leafRecord->childRecord->parentRecord->workspace);

        mock(UpdateLeafRecord::class)
            ->shouldNotReceive('handle');

        $response = patch(route('workspaces.parent-records.children.leaves.update', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]), [
            'name' => '',
        ]);

        $response->assertRedirectBackWithErrors([
            'name' => 'The name field is required.',
        ]);
    });

    it('prevents updating when dependent records exist', function (): void {
        $leafRecord = LeafRecord::factory()->createOne();

        login(workspace: $leafRecord->childRecord->parentRecord->workspace);

        mock(UpdateLeafRecord::class)
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (
                ChildRecord $childRecord,
                LeafRecord $leafRecordArgument,
                ExampleInput $input
            ): bool => $childRecord->is($leafRecord->childRecord)
                && $leafRecordArgument->is($leafRecord)
                && $input->name === 'Updated Leaf')
            ->andThrow(CannotUpdateLeafRecord::becauseDependentRecordsExist());

        $response = patch(route('workspaces.parent-records.children.leaves.update', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]), [
            'name' => 'Updated Leaf',
        ]);

        $response->assertRedirectBackWithErrors([
            'leaf_record' => 'The leaf record cannot be changed while dependent records exist.',
        ]);
    });

    it('passes partial input to the delegated action', function (): void {
        $leafRecord = LeafRecord::factory()->createOne();

        login(workspace: $leafRecord->childRecord->parentRecord->workspace);

        mock(UpdateLeafRecord::class)
            ->shouldReceive('handle')
            ->once()
            ->withArgs(fn (
                ChildRecord $childRecord,
                LeafRecord $leafRecordArgument,
                ExampleInput $input
            ): bool => $childRecord->is($leafRecord->childRecord)
                && $leafRecordArgument->is($leafRecord)
                && $input->name === 'Updated Leaf')
            ->andReturn($leafRecord);

        $response = patch(route('workspaces.parent-records.children.leaves.update', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ]), [
            'name' => 'Updated Leaf',
        ]);

        $response->assertRedirectToRoute('workspaces.parent-records.children.leaves.show', [
            'workspace' => $leafRecord->childRecord->parentRecord->workspace,
            'parent_record' => $leafRecord->childRecord->parentRecord,
            'child_record' => $leafRecord->childRecord,
            'leaf_record' => $leafRecord,
        ])
            ->assertToast('Leaf record updated');
    });
});
```

### Stored-Bound Validation Example

Use this shape only when the Form Request owns the comparison and can safely evaluate it without action-owned locks.

```php
it('validates minimum value against the stored maximum value', function (): void {
    $parentRecord = ParentRecord::factory()->createOne([
        'maximum_value' => 5,
        'minimum_value' => 3,
    ]);

    login(workspace: $parentRecord->workspace);

    $response = patch(route('workspaces.parent-records.update', [
        'workspace' => $parentRecord->workspace,
        'parent_record' => $parentRecord,
    ]), [
        'minimum_value' => 6,
    ]);

    $response->assertRedirectBackWithErrors([
        'minimum_value' => 'The minimum value field must be less than or equal to 5.',
    ]);
});
```

### Controller-Owned Range / Open-Ended Update Examples

Use these shapes only when the Form Request/controller owns the range comparison and the update persists directly at the HTTP boundary. If the same guard needs locks, transactional reads, or action-owned dependent state, keep only mocked exception-to-validation coverage here and prove the real guard in `tests/Integration/Actions`.

```php
it('does not validate range availability when other fields are invalid', function (): void {
    $leafRecord = LeafRecord::factory()
        ->forRange(0, 5)
        ->createOne();

    LeafRecord::factory()
        ->recycle($leafRecord->childRecord)
        ->forRange(10, null)
        ->createOne();

    login(workspace: $leafRecord->childRecord->parentRecord->workspace);

    $response = patch(route('workspaces.parent-records.children.leaves.update', [
        'workspace' => $leafRecord->childRecord->parentRecord->workspace,
        'parent_record' => $leafRecord->childRecord->parentRecord,
        'child_record' => $leafRecord->childRecord,
        'leaf_record' => $leafRecord,
    ]), [
        'name' => '',
        'maximum_value' => null,
    ]);

    $response->assertRedirectBackWithErrors([
        'name' => 'The name field is required.',
    ])
        ->assertSessionDoesntHaveErrors(['maximum_value', 'minimum_value']);
});

it('excludes the updated record from overlap validation', function (): void {
    $leafRecord = LeafRecord::factory()
        ->forRange(0, 2)
        ->createOne();

    login(workspace: $leafRecord->childRecord->parentRecord->workspace);

    $response = patch(route('workspaces.parent-records.children.leaves.update', [
        'workspace' => $leafRecord->childRecord->parentRecord->workspace,
        'parent_record' => $leafRecord->childRecord->parentRecord,
        'child_record' => $leafRecord->childRecord,
        'leaf_record' => $leafRecord,
    ]), [
        'name' => 'Updated without range conflict',
    ]);

    $response->assertRedirectToRoute('workspaces.parent-records.children.leaves.show', [
        'workspace' => $leafRecord->childRecord->parentRecord->workspace,
        'parent_record' => $leafRecord->childRecord->parentRecord,
        'child_record' => $leafRecord->childRecord,
        'leaf_record' => $leafRecord,
    ])
        ->assertToast('Leaf record updated');

    $leafRecord->refresh();

    expect($leafRecord)->name->toBe('Updated without range conflict');
});

it('allows clearing the maximum value', function (): void {
    $leafRecord = LeafRecord::factory()
        ->forRange(2, 10)
        ->createOne();

    login(workspace: $leafRecord->childRecord->parentRecord->workspace);

    $response = patch(route('workspaces.parent-records.children.leaves.update', [
        'workspace' => $leafRecord->childRecord->parentRecord->workspace,
        'parent_record' => $leafRecord->childRecord->parentRecord,
        'child_record' => $leafRecord->childRecord,
        'leaf_record' => $leafRecord,
    ]), [
        'maximum_value' => null,
    ]);

    $response->assertRedirectToRoute('workspaces.parent-records.children.leaves.show', [
        'workspace' => $leafRecord->childRecord->parentRecord->workspace,
        'parent_record' => $leafRecord->childRecord->parentRecord,
        'child_record' => $leafRecord->childRecord,
        'leaf_record' => $leafRecord,
    ])
        ->assertToast('Leaf record updated');

    $leafRecord->refresh();

    expect($leafRecord)->maximum_value->toBeNull();
});

it('allows minimum value updates when the stored maximum value is open ended', function (): void {
    $leafRecord = LeafRecord::factory()
        ->forRange(5, null)
        ->createOne();

    login(workspace: $leafRecord->childRecord->parentRecord->workspace);

    $response = patch(route('workspaces.parent-records.children.leaves.update', [
        'workspace' => $leafRecord->childRecord->parentRecord->workspace,
        'parent_record' => $leafRecord->childRecord->parentRecord,
        'child_record' => $leafRecord->childRecord,
        'leaf_record' => $leafRecord,
    ]), [
        'minimum_value' => 11,
    ]);

    $response->assertRedirectToRoute('workspaces.parent-records.children.leaves.show', [
        'workspace' => $leafRecord->childRecord->parentRecord->workspace,
        'parent_record' => $leafRecord->childRecord->parentRecord,
        'child_record' => $leafRecord->childRecord,
        'leaf_record' => $leafRecord,
    ])
        ->assertToast('Leaf record updated');

    $leafRecord->refresh();

    expect($leafRecord)
        ->minimum_value->toBe('11.0000')
        ->maximum_value->toBeNull();
});
```

### Additional Validation References

Load focused validation files before the broader catalog:

- `validation/required-with-and-array.md`
- `validation/scoped-exists-and-unique.md`
- `validation/prepare-for-validation.md`
- `validation/update-validates-fields.md`

### System Update Patterns

- Controller-owned update tests assert durable changes, normalized values, resolved public IDs, redirects, and toasts.
- Delegated update tests mock the action, assert route-bound model identity, direct parent/child identity when relevant, and minimal input mapping.
- Partial update cases should remain partial. Do not convert a partial update test to a full payload just to cover every input property.
- Omitted fields, `Optional`, explicit `null` clearing, stored-value side effects, and persistence belong in action integration tests when the action owns the mutation.
- Lifecycle guards that stop before validation send minimal data and assert the action was not called.
- Request-owned dependent-record restrictions can stay in Form Request validation. If the guard needs action-owned locks or transactional reads, cover it in action integration tests and keep the controller test for mapped validation.
- Scoped uniqueness on update covers same-scope duplicate failure, current-record ignore, allowed cross-scope reuse, soft-deleted reuse when permitted, and inactive reserved rows when still counted.
- Stored-value comparisons need failure cases and positive cases for omitted or open-ended stored values when the request owns them.
- Controller-owned range updates include base-validation short-circuit tests, current-record exclusion from overlap checks, nullable clearing of an open-ended maximum, and positive paths when the stored maximum is already open ended.

## Coverage Expectations

Use the live controller, routes, form requests, policies, resources, actions, and sibling tests to decide the complete update matrix. Named tests are preferred for rules that depend on stored model values or related records.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not drop applicable controller boundary coverage when adapting examples.
- Do not use real module, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
