# app/Http/Resources

## Purpose

This reference defines project conventions for JSON serialization contracts under `app/Http/Resources`.

## When To Use

Use this reference when creating or changing an API/Inertia resource, serialized field contract, nested resource, conditional resource field, or resource integration test.

## Required Pattern

Use `app/Http/Resources` for JSON serialization contracts.

### Resource Shape

- Extend `JsonResource`.
- Add `@property Model $resource` PHPDoc.
- Implement `toArray(Request $request): array`.
- Add `#[Override]` on `toArray(...)`.
- Serialize public IDs as `id`.
- Keep raw internal foreign keys out of resource output unless the resource already exposes that exact field.
- Prefer public IDs, nested resources, or controller-specific props for selected relationships.
- Serialize dates, decimals, enums, phone numbers, nested resources, and conditional fields exactly like sibling resources.
- Use `when(...)` for conditional fields. When the resource omits the default value and Laravel returns a missing value, tests should assert the key is missing. When the resource passes an explicit `null` default, tests should assert the key is present with `null`.
- Preserve explicit `null` values for nullable scalar contract fields such as coordinates, phone numbers, subdivision names, deactivation timestamps, and optional maximum ranges.

Basic shape:

```php
/**
 * @property ParentRecord $resource
 */
class ParentRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'created_at' => $this->resource->created_at,
            'deactivated_at' => $this->resource->deactivated_at,
            'id' => $this->resource->public_id,
            'name' => $this->resource->name,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
```

Deterministic derived field shape:

```php
'avatar_url' => sprintf(
    'https://avatar.example/%s',
    Str::of($this->resource->contact_email)->trim()->lower()->hash('sha256')
),
```

Conditional nested resource shape:

```php
'current_workspace' => $this->when(
    ($currentWorkspace = $this->resource->currentWorkspace) instanceof Workspace,
    fn (): WorkspaceResource => WorkspaceResource::make($currentWorkspace),
    null,
),
```

Reference-data-backed resource shape with static caches:

```php
/**
 * @var array<string, ReferenceRegion>
 */
private static array $regionsByCode = [];

private function resolveRegion(RegionCode $regionCode): ReferenceRegion
{
    if (! array_key_exists($regionCode->value, self::$regionsByCode)) {
        self::$regionsByCode[$regionCode->value] = ReferenceRegion::query()
            ->where('code', $regionCode)
            ->firstOrFail();
    }

    return self::$regionsByCode[$regionCode->value];
}
```

Be careful with static caches because long-running workers keep static state between requests. If adding one, key it by immutable reference data only and never cache request-, actor-, or `Workspace`-specific values or model instances whose authorization context can change.

### Test Mapping

- Resource serialization contracts are covered through `tests/Integration/Http/Resources`.
- Assert the complete serialized array with `toEqual([...])` for value contract coverage.
- Do not use partial match assertions for the primary resource contract coverage.
- Conditional resources need both branches: exact nested array when present and either an omitted-key assertion or explicit `null` assertion according to the resource default. The absent/null branch can assert only that conditional behavior when another test already covers the complete base contract.

## Coverage Expectations

Every resource field is part of the contract. When a resource changes, update the exact integration resource test and any controller tests that assert the same prop shape.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not expose internal integer IDs when the external contract uses public IDs.

## Related References

- `references/tests/Integration/Http/Resources/README.md`
