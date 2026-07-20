# Scoped `exists` and `unique` Validation Snippets

## Purpose

This reference defines controller feature-test snippets for scoped `exists` and `unique` validation.

## When To Use

Use this reference when validation rules depend on the current `Workspace`, direct parent, ancestor chain, public IDs, soft deletion, inactive reservation, or update `ignore(...)`.

## Required Pattern

Keep scoped validation tests close to `store` or `update`. Use the base dataset for malformed values and focused `it(...)` tests for cases that need persisted rows or a parent chain.

### Scoped `exists` Example

```php
it('validates related record belongs to the route Workspace', function (): void {
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
```

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

### Parent-Dependent `exists`

```php
it('validates option code belongs to the selected parent option', function (): void {
    $workspace = Workspace::factory()->createOne();

    login(workspace: $workspace);

    $response = post(route('workspaces.parent-records.store', [
        'workspace' => $workspace,
    ]), [
        'parent_option_code' => 'AA',
        'child_option_code' => 'ZZ',
    ]);

    $response->assertRedirectBackWithErrors([
        'child_option_code' => 'The selected child option code is invalid.',
    ]);
});
```

### Scoped `unique` on Store

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
it('allows using the same name in another Workspace', function (): void {
    ParentRecord::factory()->createOne([
        'name' => 'Example Name',
    ]);
    $workspace = Workspace::factory()->createOne();

    login(workspace: $workspace);

    $response = post(route('workspaces.parent-records.store', [
        'workspace' => $workspace,
    ]), [
        'name' => 'Example Name',
    ]);

    $parentRecord = $workspace->parentRecords()
        ->where('name', 'Example Name')
        ->sole();

    $response->assertRedirectToRoute('workspaces.parent-records.show', [
        'workspace' => $workspace,
        'parent_record' => $parentRecord,
    ]);

    assertDatabaseHas(ParentRecord::class, [
        'workspace_id' => $workspace->id,
        'name' => 'Example Name',
    ]);
});
```

```php
it('allows reusing a name after the existing record is deleted', function (): void {
    $deletedParentRecord = ParentRecord::factory()
        ->trashed()
        ->createOne([
            'name' => 'Example Name',
        ]);

    login(workspace: $deletedParentRecord->workspace);

    $response = post(route('workspaces.parent-records.store', [
        'workspace' => $deletedParentRecord->workspace,
    ]), [
        'name' => 'Example Name',
    ]);

    $response->assertRedirect();

    assertDatabaseHas(ParentRecord::class, [
        'workspace_id' => $deletedParentRecord->workspace_id,
        'name' => 'Example Name',
    ]);
});
```

### Scoped `unique` on Update with `ignore(...)`

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

```php
it('allows keeping the same name while updating', function (): void {
    $parentRecord = ParentRecord::factory()->createOne([
        'name' => 'Example Name',
    ]);

    login(workspace: $parentRecord->workspace);

    $response = patch(route('workspaces.parent-records.update', [
        'workspace' => $parentRecord->workspace,
        'parent_record' => $parentRecord,
    ]), [
        'name' => $parentRecord->name,
    ]);

    $response->assertRedirectToRoute('workspaces.parent-records.show', [
        'workspace' => $parentRecord->workspace,
        'parent_record' => $parentRecord,
    ]);

    assertDatabaseHas(ParentRecord::class, [
        'id' => $parentRecord->id,
        'name' => 'Example Name',
    ]);
});
```

## Coverage Expectations

For scoped `exists`, cover out-of-scope records, soft-deleted related records when applicable, inactive related records when selectors are active-only, and current-record continuity exceptions when implemented.

For scoped `unique`, cover same-scope duplicate failure, allowed cross-scope duplicate when applicable, update current-record `ignore(...)`, soft-deleted reuse when the rule or index excludes trashed rows, and inactive-record reservation when the rule still counts non-soft-deleted rows.

Do not turn inactive uniqueness reservation into an active-only selector rule. Uniqueness asks whether a value is still reserved; selectable relationships ask whether a related record may be chosen.

Range or overlap validation belongs here only when the Form Request owns the rule. If it needs locks or transactional state, prove it in action integration tests and keep controller coverage for mapped action exceptions.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not drop applicable validation coverage when adapting examples.
- Do not use real module, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
