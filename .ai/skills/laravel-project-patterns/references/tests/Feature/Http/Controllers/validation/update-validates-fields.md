# Update Validation Dataset Snippets

## Purpose

This reference is a catalog of update validation dataset snippets for controller feature tests.

## When To Use

Use this file only after loading focused validation references that match the actual update rules.

## Required Pattern

This file is a catalog. Action templates already include a baseline `validates fields` test. Merge only the extra rules the update request actually uses.

Conventions:

- Keep each dataset `data` minimal.
- Assert exact message strings for every failing field.
- Order dataset cases alphabetically by dataset key unless the nearest sibling has a clearer field-specific order.
- For `sometimes|required`, prefer one blank-value case covering every field that shares the rule.
- Paired fields include both `required_with` directions and each range boundary.
- UI-only or server-managed inputs use `missing` or `prohibited` cases only when the Form Request explicitly rejects submitted values.
- Use named tests outside the dataset for stored model values or related records.
- Validation tests use an actor authorized for the route `Workspace` so validation runs after authorization.

Load focused files first:

- `validation/required-with-and-array.md`
- `validation/scoped-exists-and-unique.md`
- `validation/prepare-for-validation.md`

```php
it('validates fields', function (array $data, array $expected): void {
    $parentRecord = ParentRecord::factory()->createOne();

    login(workspace: $parentRecord->workspace);

    $response = patch(route('workspaces.parent-records.update', [
        'workspace' => $parentRecord->workspace,
        'parent_record' => $parentRecord,
    ]), $data);

    $response->assertRedirectBackWithErrors($expected);
})->with([
    'boolean' => [
        'data' => [
            'is_default' => 'not-boolean',
        ],
        'expected' => [
            'is_default' => 'The is default field must be true or false.',
        ],
    ],
    'decimal:0,4' => [
        'data' => [
            'minimum_value' => 12.12345,
            'maximum_value' => 15.12345,
        ],
        'expected' => [
            'minimum_value' => 'The minimum value field must have 0-4 decimal places.',
            'maximum_value' => 'The maximum value field must have 0-4 decimal places.',
        ],
    ],
    'contact email' => [
        'data' => [
            'contact_email' => 'invalid',
        ],
        'expected' => [
            'contact_email' => 'The contact email field must be a valid email address.',
        ],
    ],
    'enum' => [
        'data' => [
            'example_mode' => 'invalid',
        ],
        'expected' => [
            'example_mode' => 'The selected example mode is invalid.',
        ],
    ],
    'exists' => [
        'data' => [
            'related_record_id' => 'not-a-public-id',
        ],
        'expected' => [
            'related_record_id' => 'The selected related record id is invalid.',
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
    'missing' => [
        'data' => [
            'server_managed_value' => 'submitted',
        ],
        'expected' => [
            'server_managed_value' => 'The server managed value field must be missing.',
        ],
    ],
    'prohibited' => [
        'data' => [
            'immutable_related_record_id' => 'related-record-id',
        ],
        'expected' => [
            'immutable_related_record_id' => 'The immutable related record id field is prohibited.',
        ],
    ],
    'required_if' => [
        'data' => [
            'example_mode' => 'advanced',
        ],
        'expected' => [
            'conditional_value' => 'The conditional value field is required.',
        ],
    ],
    'sometimes (required)' => [
        'data' => [
            'contact_email' => '',
            'name' => '',
        ],
        'expected' => [
            'contact_email' => 'The contact email field is required.',
            'name' => 'The name field is required.',
        ],
    ],
]);
```

### Scoped Unique on Update

```php
it('validates name uniqueness within the same Workspace on update', function (): void {
    $parentRecord = ParentRecord::factory()->createOne([
        'name' => 'Example Name',
    ]);
    $otherParentRecord = ParentRecord::factory()
        ->recycle($parentRecord->workspace)
        ->createOne([
            'name' => 'Other Name',
        ]);

    login(workspace: $parentRecord->workspace);

    $response = patch(route('workspaces.parent-records.update', [
        'workspace' => $parentRecord->workspace,
        'parent_record' => $otherParentRecord,
    ]), [
        'name' => 'Example Name',
    ]);

    $response->assertRedirectBackWithErrors([
        'name' => 'The name has already been taken.',
    ]);
});
```

### Stored-Value Comparison

```php
it('validates minimum value against stored maximum value', function (): void {
    $parentRecord = ParentRecord::factory()->createOne([
        'minimum_value' => 2,
        'maximum_value' => 5,
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

### Payload-Level Error Bag

```php
it('requires at least one displayable value', function (): void {
    $parentRecord = ParentRecord::factory()->createOne();

    login(workspace: $parentRecord->workspace);

    $response = patch(route('workspaces.parent-records.update', [
        'workspace' => $parentRecord->workspace,
        'parent_record' => $parentRecord,
    ]), [
        'contact_email' => '',
        'first_name' => '',
        'last_name' => '',
        'contact_number' => '',
    ]);

    $response->assertRedirectBackWithErrors([
        'summary' => 'Please provide at least one displayable value.',
    ], null, '_general');
});
```

### Request-Owned Dependent Record Prohibition

Use this shape only when the Form Request owns the rule before the action runs. If the guard needs action-owned locks, transactional reads, or exception mapping from a delegated action, prove the guard in `tests/Integration/Actions` and keep the controller test focused on mocked exception-to-validation mapping.

```php
it('validates a field is prohibited when dependent records exist', function (): void {
    $leafRecord = LeafRecord::factory()->createOne();

    login(workspace: $leafRecord->childRecord->parentRecord->workspace);

    $response = patch(route('workspaces.parent-records.children.leaves.update', [
        'workspace' => $leafRecord->childRecord->parentRecord->workspace,
        'parent_record' => $leafRecord->childRecord->parentRecord,
        'child_record' => $leafRecord->childRecord,
        'leaf_record' => $leafRecord,
    ]), [
        'locked_value' => 'changed',
    ]);

    $response->assertRedirectBackWithErrors([
        'locked_value' => 'The locked value field is prohibited.',
    ]);
});
```

## Coverage Expectations

For update datasets, include server-managed missing fields, immutable-field prohibitions, scoped exists rules, soft-deleted related-record rejection, paired `required_with` directions, conditional required rules, decimal precision, numeric bounds, string maximums, enum cases, range boundaries, request-owned dependent-record prohibitions, stored-value comparisons, positive partial/open-ended paths, and named general-error-bag payload failures when those rules exist.

Put persisted-row or domain failures in named tests. Put action-owned transactional guards in action integration tests and keep controller coverage for mocked exception-to-validation mapping.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not drop applicable validation coverage when adapting examples.
- Do not use real module, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
