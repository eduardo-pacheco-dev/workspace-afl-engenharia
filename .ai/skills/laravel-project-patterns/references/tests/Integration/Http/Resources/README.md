# tests/Integration/Http/Resources

## Purpose

This reference defines conventions for exact resource contract tests under `tests/Integration/Http/Resources`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/Integration/Http/Resources/<Resource>Test.php` for exact JSON resource contracts.

### File Shape

- Create one persisted model using the factory and explicit values for every serialized field that matters.
- Convert through the model's resource path:

```php
$resource = json_decode($model->toResource()->toJson(), true);
```

- Assert the full value contract with `toEqual([...])`.
- Keep key order in the expected array aligned with the resource output for readability. If key order itself is the contract, assert `array_keys($resource)` with `toBe([...])`; `toEqual([...])` alone does not fail on reordered associative keys.

### Required Assertion Style

Resource tests should assert the full serialized array value contract. Do not use partial match assertions for primary contract coverage.

Expected pattern:

```php
expect($resource)->toEqual([
    'created_at' => $model->created_at->toJSON(),
    'id' => $model->public_id,
    'name' => 'Example',
    'updated_at' => $model->updated_at->toJSON(),
]);
```

Derived deterministic field pattern:

```php
$actor = Actor::factory()->createOne([
    'contact_email' => ' actor@example.test ',
]);

$resource = json_decode($actor->toResource()->toJson(), true);

expect($resource)->toEqual([
    'avatar_url' => sprintf(
        'https://avatar.example/%s',
        '13f1cbf5226d40a9edc5bfcd7977fdfaa543f5cd85bb171d778eaf23977ce2fb'
    ),
    'contact_email' => $actor->contact_email,
    'id' => $actor->public_id,
]);
```

### Serialization Rules

- `id` is the public id, not the internal database id.
- Do not expose raw relationship integer IDs unless the actual resource explicitly includes them. Prefer nested resources or public IDs when that is the project contract.
- Dates use `toJSON()`.
- Decimal casts serialize as strings with the configured scale.
- Enums serialize as backing values.
- Deterministic derived fields should be asserted as final serialized values, not by re-running the same transformation in the expectation.
- Phone numbers and value objects must match the exact resource formatting.
- Nested resources should be asserted as full nested arrays when included.
- Conditional fields need both branches when the resource has conditional branches. If the resource returns Laravel's missing value, assert the key is absent. If the resource passes an explicit `null` default, assert the key is present with `null`. That branch may assert only the conditional behavior when another test already covers the base serialized contract.
- Address-style payloads are nested contract arrays. Assert region/subdivision display names, region/subdivision codes, coordinates, normalized phone number output, and null fields exactly.
- `deactivated_at` is asserted as the serialized value the resource returns: use `null` when null, and `toJSON()` only when the resource emits a timestamp instance/string.

### Fixture Rules

- Set explicit values instead of relying on faker for serialized fields.
- Use coherent related models when the resource reads nested relationships.
- Use explicit synthetic person names unless the contract needs another value.

### Update Triggers

Update this path whenever a resource adds, removes, renames, reorders, reformats, conditionally hides, or nests fields.

## Coverage Expectations

Every resource class should have an exact contract test when it serializes a project-facing model. Update resource tests whenever controller props rely on the resource output, even if the controller test only checks selected paths.
If a controller page uses a resource for list/detail props, the controller test may assert only the public id and page context, but the resource test must still assert the complete serialization format. Add a separate `array_keys(...)` assertion when field order is intentionally part of the contract.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/Http/Resources/README.md`
