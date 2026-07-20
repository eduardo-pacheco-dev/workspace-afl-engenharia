# Store Validation Dataset Snippets

## Purpose

This reference is a catalog of store validation dataset snippets for controller feature tests.

## When To Use

Use this file only after loading focused validation references that match the actual request rules.

## Required Pattern

This file is a catalog. Action templates already include a baseline `validates fields` test. Merge only the extra rules the store request actually uses.

Conventions:

- Keep each dataset `data` minimal.
- Assert exact message strings for every failing field.
- Order dataset cases alphabetically by dataset key unless the nearest sibling has a clearer field-specific order.
- Paired fields include both `required_with` directions and each range boundary.
- UI-only or server-managed inputs use `missing` or `prohibited` cases only when the Form Request explicitly rejects submitted values.
- Validation tests use an actor authorized for the route `Workspace` so validation runs after authorization.

Load focused files first:

- `validation/required-with-and-array.md`
- `validation/scoped-exists-and-unique.md`
- `validation/prepare-for-validation.md`
- `validation/api-login-validation.md`

```php
it('validates fields', function (array $data, array $expected): void {
    $workspace = Workspace::factory()->createOne();

    login(workspace: $workspace);

    $response = post(route('workspaces.parent-records.store', [
        'workspace' => $workspace,
    ]), $data);

    $response->assertRedirectBackWithErrors($expected);
})->with([
    'array' => [
        'data' => [
            'schedule' => 'not-array',
        ],
        'expected' => [
            'schedule' => 'The schedule field must be an array.',
        ],
    ],
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
            'minimum_value' => '12.12345',
            'maximum_value' => '15.12345',
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
    'numeric' => [
        'data' => [
            'minimum_value' => 'invalid',
        ],
        'expected' => [
            'minimum_value' => 'The minimum value field must be a number.',
        ],
    ],
    'required' => [
        'data' => [],
        'expected' => [
            'name' => 'The name field is required.',
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
]);
```

```php
it('validates name uniqueness within the same Workspace', function (): void {
    $parentRecord = ParentRecord::factory()->createOne([
        'name' => 'Example Name',
    ]);

    login(workspace: $parentRecord->workspace);

    $response = post(route('workspaces.parent-records.store', [
        'workspace' => $parentRecord->workspace,
    ]), [
        'name' => 'Example Name',
    ]);

    $response->assertRedirectBackWithErrors([
        'name' => 'The name has already been taken.',
    ]);
});
```

```php
it('requires at least one displayable value', function (): void {
    $workspace = Workspace::factory()->createOne();

    login(workspace: $workspace);

    $response = post(route('workspaces.parent-records.store', [
        'workspace' => $workspace,
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

## Coverage Expectations

For store datasets, include server-managed missing fields, parent-dependent scoped exists, soft-deleted related-record rejection, paired `required_with` directions, conditional required rules, decimal precision, numeric bounds, string maximums, enum cases, range boundaries, and named general-error-bag payload failures when the request uses those rules.

Put persisted-row or domain failures in named tests. Put action-owned transactional guards in action integration tests and keep controller coverage for mocked exception-to-validation mapping.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not drop applicable validation coverage when adapting examples.
- Do not use real module, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
