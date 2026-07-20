# app/Models/Concerns

## Purpose

This reference defines project conventions for reusable Eloquent behavior shared by models.

## When To Use

Use this reference when creating or changing a model concern, shared model scope, generated route-key behavior, public ID finder, or reusable lifecycle helper.

## Required Pattern

Use `app/Models/Concerns` for reusable Eloquent behavior shared by models.

### Concern Shape

- Keep concerns narrow and model-focused.
- Use typed methods and relationship-safe query logic.
- Define local Eloquent scopes with `#[Scope]` on protected methods.
- Prefer `$builder` as the first parameter, place dynamic scope parameters after it, and return `void` when mutating the builder in place.
- Use `$builder->qualifyColumn(...)` inside reusable concern scopes so constraints stay unambiguous when callers compose the scope with joins or relationship queries.
- Do not use legacy `scopeFoo(...)` methods for new concerns unless a sibling concern already uses that older pattern.
- If a concern changes route-key behavior, public IDs, generated IDs, validation, or finder methods, treat that as a cross-model contract.

### Deactivation Concern

Use a deactivation concern only for models with a nullable `deactivated_at` timestamp cast to `datetime`.

- Keep the trait limited to generic model behavior: `isActive()`, `isDeactivated()`, idempotent `deactivate()` / `reactivate()` mutations, and `active()` / `deactivated()` local scopes.
- Keep `deactivated_at` documented as `@property-read null|CarbonImmutable $deactivated_at` on the trait and on consuming models.
- Do not add a global scope for deactivation. Deactivated records remain historically visible unless the caller explicitly applies the local scope.
- Do not hide workflow rules inside the trait. Controllers, policies, requests, actions, and transactional mutations still own authorization, validation, stale-state checks, locking, redirects, and interface messages.
- Use `$model->update([...])` for persisted state changes because models are globally unguarded.

```php
public function deactivate(): void
{
    if ($this->isActive()) {
        $this->update(['deactivated_at' => now()]);
    }
}

/**
 * @param Builder<static> $builder
 */
#[Scope]
protected function active(Builder $builder): void
{
    $builder->whereNull($builder->qualifyColumn('deactivated_at'));
}
```

### Public ID Concern

Use a public ID concern for public NanoID generation, finder, and route-key behavior.

- Use `HasUniqueStringIds` and generate IDs with the NanoID generator and the configured alphabet enum's alphanumeric case.
- Keep `PUBLIC_ID_LENGTH` at `10` unless migrations, tests, and public ID format checks change together.
- By default, `getRouteKeyName()` returns `public_id` and `uniqueIds()` returns `['public_id']`.
- Models may still use the trait for NanoID generation/finders while overriding route binding to another key such as `slug`.
- `wherePublicId(string $publicId)` is the reusable local scope for public ID constraints. Finder methods should delegate to this scope so composed queries and direct finder calls share the same behavior.
- `findByPublicId(string $publicId): null|static` and `findOrFailByPublicId(string $publicId): static` query the `public_id` column through the local scope. Use them at HTTP/form boundaries after validation.
- `isValidUniqueId()` accepts case-insensitive alphanumeric IDs matching the configured length. The database uses case-insensitive text plus format checks, so route binding and finder tests must cover case-insensitive behavior.

```php
public function newUniqueId(): string
{
    return resolve(NanoidGenerator::class)->formattedId(ExampleAlphabet::Alphanumeric(), self::PUBLIC_ID_LENGTH);
}

/**
 * @param Builder<static> $builder
 */
#[Scope]
protected function wherePublicId(Builder $builder, string $publicId): void
{
    $builder->where($builder->qualifyColumn('public_id'), $publicId);
}
```

### Test Mapping

- Persisted concern APIs are covered through `tests/Integration/Models/Concerns`.
- Route-binding behavior from a concern is covered through `tests/Feature/Models/Concerns`.
- Use test support models when the behavior is generic.

## Coverage Expectations

Read the live concern, consuming models, migrations, and relevant tests. Cover cross-model behavior in the suite that owns the contract.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not move workflow-specific authorization, validation, or locking rules into a generic concern.

## Related References

- `references/app/Models/README.md`
- `references/tests/Integration/Models/Concerns/README.md`
- `references/tests/Feature/Models/Concerns/README.md`
