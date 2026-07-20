# Paired and Conditional Validation Snippets

## Purpose

This reference defines snippets for `required_with`, `present_with`, conditional required rules, array, and paired-field validation in controller feature tests.

## When To Use

Use this reference when request rules include `required_with`, `present_with`, `required_if` or `Rule::requiredIf(...)`, `array`, nested array validation, or paired nullable/range-style fields.

## Required Pattern

Order dataset cases alphabetically by dataset key. Keep paired validation cases together within that order, include both directions, and use minimal payloads that trigger only the rule under test.

For partial updates of nullable pairs, combine reciprocal `required_with` with reciprocal `present_with`. Keep `required_with` first so non-null one-sided payloads preserve the required-field message; `present_with` covers explicitly `null` one-sided payloads while omission of both fields remains valid.

### Store Example

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
    'required_if:example_mode' => [
        'data' => [
            'example_mode' => 'advanced',
        ],
        'expected' => [
            'conditional_value' => 'The conditional value field is required.',
        ],
    ],
    'required_with:end_value' => [
        'data' => [
            'end_value' => 1,
        ],
        'expected' => [
            'start_value' => 'The start value field is required when end value is present.',
        ],
    ],
    'required_with:start_value' => [
        'data' => [
            'start_value' => 1,
        ],
        'expected' => [
            'end_value' => 'The end value field is required when start value is present.',
        ],
    ],
]);
```

### Update Example

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
    'array' => [
        'data' => [
            'schedule' => 'not-array',
        ],
        'expected' => [
            'schedule' => 'The schedule field must be an array.',
        ],
    ],
    'present_with:end_value' => [
        'data' => [
            'end_value' => null,
        ],
        'expected' => [
            'start_value' => 'The start value field must be present when end value is present.',
        ],
    ],
    'present_with:start_value' => [
        'data' => [
            'start_value' => null,
        ],
        'expected' => [
            'end_value' => 'The end value field must be present when start value is present.',
        ],
    ],
    'required_if:example_mode' => [
        'data' => [
            'example_mode' => 'advanced',
        ],
        'expected' => [
            'conditional_value' => 'The conditional value field is required.',
        ],
    ],
    'required_with:end_value' => [
        'data' => [
            'end_value' => 1,
        ],
        'expected' => [
            'start_value' => 'The start value field is required when end value is present.',
        ],
    ],
    'required_with:start_value' => [
        'data' => [
            'start_value' => 1,
        ],
        'expected' => [
            'end_value' => 'The end value field is required when start value is present.',
        ],
    ],
]);
```

## Coverage Expectations

Paired fields need both directions plus numeric/range boundaries when the request uses them. For partial updates of nullable pairs, add `present_with` cases for explicitly `null` one-sided payloads in both directions. Do not add equivalent store cases unless payload-key symmetry is itself part of the create contract. Conditional required rules need a payload that activates the condition while leaving the dependent field absent. Server-managed or non-contract fields belong in the same validation dataset only when the Form Request explicitly rejects them with `missing` or `prohibited`.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not drop applicable validation coverage when adapting examples.
- Do not use real module, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
