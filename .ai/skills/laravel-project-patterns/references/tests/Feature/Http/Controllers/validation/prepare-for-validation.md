# `prepareForValidation()` Validation Snippets

## Purpose

This reference defines controller feature-test snippets for request normalization and stored-value comparisons.

## When To Use

Use this reference when a Form Request mutates incoming data before rule evaluation or when update validation compares submitted values with stored model values.

## Required Pattern

Do not use `prepareForValidation()` by default. First check whether validation can run against the submitted public contract directly. For public IDs submitted by forms, prefer validating public IDs and resolving to internal IDs in the controller when that keeps the request contract honest.

### Public ID Validation With Controller Resolution

```php
it('validates fields', function (array $data, array $expected): void {
    $parentRecord = ParentRecord::factory()->createOne();

    login(workspace: $parentRecord->workspace);

    $response = post(route('workspaces.parent-records.children.store', [
        'workspace' => $parentRecord->workspace,
        'parent_record' => $parentRecord,
    ]), $data);

    $response->assertRedirectBackWithErrors($expected);
})->with([
    'exists' => [
        'data' => [
            'related_record_id' => 'not-a-public-id',
        ],
        'expected' => [
            'related_record_id' => 'The selected related record id is invalid.',
        ],
    ],
]);
```

```php
it('creates a child record with a related record public id', function (): void {
    $parentRecord = ParentRecord::factory()->createOne();
    $relatedRecord = RelatedRecord::factory()
        ->for($parentRecord->workspace)
        ->createOne();

    login(workspace: $parentRecord->workspace);

    $response = post(route('workspaces.parent-records.children.store', [
        'workspace' => $parentRecord->workspace,
        'parent_record' => $parentRecord,
    ]), [
        'related_record_id' => $relatedRecord->public_id,
    ]);

    $childRecord = ChildRecord::query()
        ->where('parent_record_id', $parentRecord->id)
        ->where('related_record_id', $relatedRecord->id)
        ->sole();

    $response->assertRedirectToRoute('workspaces.parent-records.children.show', [
        'workspace' => $parentRecord->workspace,
        'parent_record' => $parentRecord,
        'child_record' => $childRecord,
    ]);

    assertDatabaseHas(ChildRecord::class, [
        'parent_record_id' => $parentRecord->id,
        'related_record_id' => $relatedRecord->id,
    ]);
});
```

### Merge Route Model Value When a Field Is Blank

```php
it('updates when parent option is empty but matches the existing stored value', function (): void {
    $childRecord = ChildRecord::factory()->createOne([
        'parent_option_code' => 'AA',
    ]);

    login(workspace: $childRecord->parentRecord->workspace);

    $response = patch(route('workspaces.parent-records.children.update', [
        'workspace' => $childRecord->parentRecord->workspace,
        'parent_record' => $childRecord->parentRecord,
        'child_record' => $childRecord,
    ]), [
        'parent_option_code' => '',
        'child_option_code' => 'BB',
    ]);

    $response->assertRedirectToRoute('workspaces.parent-records.children.show', [
        'workspace' => $childRecord->parentRecord->workspace,
        'parent_record' => $childRecord->parentRecord,
        'child_record' => $childRecord,
    ])
        ->assertToast('Child record updated');

    assertDatabaseHas(ChildRecord::class, [
        'id' => $childRecord->id,
        'parent_option_code' => 'AA',
        'child_option_code' => 'BB',
    ]);
});
```

### Stored-Bound Cross-Field Validation

Use this shape only when the request owns the comparison and does not require action-owned locks or dependent-row state.

```php
it('validates minimum value against the stored maximum value', function (): void {
    $parentRecord = ParentRecord::factory()->createOne([
        'minimum_value' => 3,
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

```php
it('validates maximum value against the stored minimum value', function (): void {
    $leafRecord = LeafRecord::factory()->createOne([
        'minimum_value' => 10,
        'maximum_value' => 15,
    ]);

    login(workspace: $leafRecord->childRecord->parentRecord->workspace);

    $response = patch(route('workspaces.parent-records.children.leaves.update', [
        'workspace' => $leafRecord->childRecord->parentRecord->workspace,
        'parent_record' => $leafRecord->childRecord->parentRecord,
        'child_record' => $leafRecord->childRecord,
        'leaf_record' => $leafRecord,
    ]), [
        'maximum_value' => 5,
    ]);

    $response->assertRedirectBackWithErrors([
        'maximum_value' => 'The maximum value field must be greater than or equal to 10.',
    ]);
});
```

### Normalized Value Before Unique Validation

When a request normalizes a public input field before rule evaluation and uniqueness depends on the persisted normalized value, cover semantically equivalent input instead of internal persistence columns.

```php
it('validates contact value uniqueness using the normalized value', function (): void {
    $parentRecord = ParentRecord::factory()->createOne([
        'contact_value' => '+10000000000',
    ]);

    login(workspace: $parentRecord->workspace);

    $response = post(route('workspaces.parent-records.store', [
        'workspace' => $parentRecord->workspace,
    ]), [
        'contact_value' => '+1 000 000 0000',
    ]);

    $response->assertRedirectBackWithErrors([
        'contact_value' => 'The contact value has already been taken.',
    ]);
});
```

## Coverage Expectations

Cover both sides of request normalization: validation failures for malformed or out-of-scope input, and success cases proving the normalized or resolved value is persisted exactly. If a public ID is resolved after `$request->validated()`, assert the resolved database column and public route key in the controller success test; do not submit internal integer IDs.

Domain guards that require locks or dependent records moving concurrently belong in actions. Controller tests should then mock exception-to-validation mapping and action integration tests should prove the real guard.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not drop applicable validation coverage when adapting examples.
- Do not use real module, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
