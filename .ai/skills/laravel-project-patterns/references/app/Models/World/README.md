# app/Models/World

## Purpose

This reference defines project conventions for read-only reference-data models backed by a non-default database connection.

## When To Use

Use this reference when creating or changing a reference-data model under `app/Models/World`, a relationship between those models, or tests for behavior that depends on records from a non-default connection.

## Required Pattern

Use `app/Models/World` for read-only reference-data models backed by a non-default connection.

### Reference Data Model Shape

- Use a `#[Connection(...)]` attribute on each model so Eloquent reads from the non-default connection configured for that data source.
- Keep relationships typed with Eloquent generic PHPDoc.
- Treat these models as reference data; avoid write flows unless the application explicitly adds one.
- Relationships should mirror the reference-data hierarchy, for example a top-level record has many middle and leaf records, a middle record belongs to the top-level record and has many leaf records, and a leaf record belongs to both.
- Use scalar PHPDoc matching the imported reference-data schema.
- Coordinate PHPDoc may use `float` or `null|float` depending on whether the imported source guarantees a value. Do not add casts only to paper over source-storage differences unless tests require it.
- Keep timestamp PHPDoc as `CarbonImmutable`.
- Preserve source-schema camel-case columns in PHPDoc when the imported schema uses them.

```php
#[Connection('reference_data')]
class ReferenceMiddle extends Model
{
    /**
     * @return BelongsTo<ReferenceTopLevel, $this>
     */
    public function topLevel(): BelongsTo
    {
        return $this->belongsTo(ReferenceTopLevel::class);
    }

    /**
     * @return HasMany<ReferenceLeaf, $this>
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(ReferenceLeaf::class);
    }
}
```

### Test Mapping

- Reference-data persisted tests belong under `tests/Integration/Models` only when they prove project/system behavior that depends on records from the non-default connection.
- Keep relationship queries bounded when the bounded query itself is domain behavior.
- Do not create broad fixtures for the reference-data database.

## Coverage Expectations

Read the live reference-data model, imported schema shape, and the feature/resource/request code that consumes it. Cover only project behavior that depends on reference-data records.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not add write behavior to reference-data models without an explicit application contract.

## Related References

- `references/app/Models/README.md`
- `references/tests/Integration/Models/README.md`
