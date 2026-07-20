# Models and Relationships

## Purpose

This reference defines project conventions for Eloquent models, relationships, casts, accessors, route keys, and model PHPDoc.

## When To Use

Use this reference when creating or changing Eloquent models, traits, casts, accessors, model relationships, route-key behavior, or model-focused tests.

## Required Pattern

Use this reference when creating or changing schema-backed Eloquent models.

### Model Shape

Examples use synthetic names. Keep examples synthetic in this reference; when editing real code, preserve the live columns, route key, casts, relationships, and ownership graph from sibling models.

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasDeactivation;
use App\Models\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Database\Factories\ParentRecordFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * @property-read int $id
 * @property-read string $public_id
 * @property-read int $workspace_id
 * @property-read null|CarbonImmutable $deactivated_at
 * @property-read string $name
 * @property-read CarbonImmutable $created_at
 * @property-read CarbonImmutable $updated_at
 * @property-read null|CarbonImmutable $deleted_at
 * @property-read Collection<int, ChildRecord> $children
 * @property-read Workspace $workspace
 */
class ParentRecord extends Model
{
    use HasDeactivation;

    /** @use HasFactory<ParentRecordFactory> */
    use HasFactory;

    use HasPublicId;
    use SoftDeletes;

    /**
     * @return HasMany<ChildRecord, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(ChildRecord::class);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'deactivated_at' => 'datetime',
        ];
    }
}
```

### Core Rules

- Use strict types and explicit imports.
- Do not add `$fillable` or `$guarded` when the app globally calls `Model::unguard()`.
- Use `$model->update([...])` for normal persisted attribute mutations in app-owned code. Do not use `forceFill(...)->save()` as a mass-assignment workaround.
- Add `HasFactory` with a generic PHPDoc `@use HasFactory<ModelFactory>`.
- Add `HasDeactivation` for models with a nullable `deactivated_at` lifecycle timestamp. Keep it as explicit domain state, not as `SoftDeletes` or a global scope.
- Add `HasPublicId` for routeable or externally exposed domain models with a `public_id` column.
- Add `SoftDeletes` only when the migration includes `softDeletes()`.
- Let Pint order class trait uses, but keep the `@use HasFactory<ModelFactory>` PHPDoc directly attached to the `HasFactory` use statement.
- Add `#[Override]` on overridden framework hooks/properties/methods where sibling code does.
- Use `protected function casts(): array` instead of a `$casts` property when sibling models use the method.
- Keep model defaults in `protected $attributes = [...]` when defaults are part of the domain.
- Use enum casts for enum-backed strings; use `decimal:n`, `integer`, `boolean`, `array`, `float`, `hashed`, value-object casts, and `datetime` casts for scalar normalization.
- Use `CarbonImmutable` in PHPDoc for timestamps when project date behavior expects immutable timestamps in tests.

Default attribute pattern:

```php
#[Override]
protected $attributes = [
    'enabled' => false,
    'mode' => ExampleMode::Default,
];
```

### Relationships

- Always type relationship methods with Laravel relation return types.
- Add PHPDoc generics above relationship methods:
  - `@return BelongsTo<ParentRecord, $this>`
  - `@return HasMany<ChildRecord, $this>`
  - `@return HasOne<ChildRecord, $this>`
  - `@return BelongsToMany<RelatedRecord, $this, Membership, 'membership'>`
- For non-conventional foreign keys, pass the key explicitly.
- For pivot models that use incrementing IDs, set `public $incrementing = true;` on the custom pivot class.
- If a relationship has scoped behavior, name it descriptively, for example `defaultChild()` for `hasOne(...)->where('is_default', true)`.
- Model ownership follows the stored IDs. Direct children belong to their direct parent or `Workspace`. Deeper children resolve ownership through their parent chain.
- When a child stores denormalized ownership, keep both relationship paths explicit and test the invalid graph only where route/list/authorization behavior needs it.
- Create nested children through the parent relationship when that relationship defines the domain boundary.

Pivot relationship pattern:

```php
/**
 * @return BelongsToMany<Workspace, $this, Membership, 'membership'>
 */
public function workspaces(): BelongsToMany
{
    return $this->belongsToMany(Workspace::class)
        ->as('membership')
        ->using(Membership::class)
        ->withTimestamps();
}
```

Accessor pattern:

```php
/**
 * @return Attribute<string, never>
 */
protected function displayName(): Attribute
{
    return Attribute::get(fn (): string => $this->name ?? $this->secondary_label ?? '');
}
```

### Local Query Scopes

- Define new local Eloquent scopes with Laravel's `#[Scope]` attribute on protected methods. Do not use legacy `scopeFoo(...)` methods for new code unless an existing file already establishes that local pattern.
- Import `Illuminate\Database\Eloquent\Attributes\Scope` and `Illuminate\Database\Eloquent\Builder` for scoped methods.
- Name the first parameter `$builder`. For dynamic scopes, place additional parameters after the builder.
- Return `void` when mutating the builder in place.
- Use `$builder->qualifyColumn(...)` when filtering a column owned by the scoped model, especially inside reusable concerns or scopes that may be composed with joins or relationship queries.
- Do not add a standalone test for a simple scope wrapper when a public method, finder, controller path, or system behavior already proves the same query constraint.

```php
/**
 * @param Builder<static> $builder
 */
#[Scope]
protected function wherePublicId(Builder $builder, string $publicId): void
{
    $builder->where($builder->qualifyColumn('public_id'), $publicId);
}
```

### Route Keys and Public IDs

- `HasPublicId` provides `getRouteKeyName(): string` returning `public_id` and `uniqueIds(): array` returning `['public_id']`.
- `HasPublicId` also provides `findByPublicId(...)` and `findOrFailByPublicId(...)`; use these helpers instead of hand-written public ID queries when resolving validated public IDs.
- Public IDs are 10-character alphanumeric Nano IDs stored in case-insensitive text columns with database format checks; route binding accepts case-insensitive public IDs.
- Use slug route keys only for models that are explicitly slug-backed. Override `getRouteKeyName()` to return `slug` and test slug generation/stability.
- Do not expose numeric IDs in routes when sibling domain models use `public_id` or slug.
- Keep internal integer IDs in database columns and serialized public IDs in resources/routes/form values. Convert at boundaries instead of leaking internal IDs into external contracts.

Slug override pattern:

```php
#[Sluggable(from: 'name', to: 'slug', onUpdate: false)]
class Workspace extends Model
{
    use HasPublicId;

    #[Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
```

For slug-backed models, generate the slug from the display name once, keep route binding on `slug`, and prove creation plus non-regeneration on update in `tests/Integration/Models`.

Manual route-key override pattern:

```php
#[Override]
public function getRouteKeyName(): string
{
    return 'slug';
}
```

Auth/current-`Workspace` model pattern:

```php
#[Hidden(['password', 'remember_token'])]
class Actor extends Authenticatable implements AdminPanelUser
{
    use HasApiTokens;

    /** @use HasFactory<ActorFactory> */
    use HasFactory;

    use HasPublicId;
    use Notifiable;

    public function belongsToWorkspace(null|Workspace $workspace): bool
    {
        if (! $workspace instanceof Workspace) {
            return false;
        }

        if ($this->ownsWorkspace($workspace)) {
            return true;
        }

        return $this->workspaces->contains($workspace);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function currentWorkspace(): BelongsTo
    {
        if ($this->current_workspace_id === null && ($workspace = $this->workspaces->first()) !== null) {
            $this->switchWorkspace($workspace);
        }

        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    public function isAdmin(): bool
    {
        return in_array($this->email, Config::array('admin.emails'), true);
    }

    public function isCurrentWorkspace(Workspace $workspace): bool
    {
        if ($this->currentWorkspace === null) {
            return false;
        }

        return $this->currentWorkspace->id === $workspace->id;
    }

    public function ownsWorkspace(null|Workspace $workspace): bool
    {
        if (! $workspace instanceof Workspace) {
            return false;
        }

        return $this->id === $workspace->owner_id;
    }

    public function switchWorkspace(Workspace $workspace): bool
    {
        if (! $this->belongsToWorkspace($workspace)) {
            return false;
        }

        $this->update([
            'current_workspace_id' => $workspace->id,
        ]);

        $this->setRelation('currentWorkspace', $workspace);

        return true;
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'name' => PersonNameCast::class,
            'password' => 'hashed',
        ];
    }
}
```

Keep hidden auth fields declared with `#[Hidden(...)]`, use `hashed` casts for passwords, put panel access behind a small config-backed predicate, and cover current-`Workspace` persisted state transitions in `tests/Integration/Models`.

### Domain Constraints

- `deactivated_at` is a domain state, not a soft delete. Cast it to `datetime`, include it in PHPDoc as `null|CarbonImmutable`, use `HasDeactivation` for reusable active/deactivated scopes and idempotent state transitions, and keep `deactivated()` factory states when siblings have them.
- `HasDeactivation` provides local `active()` / `deactivated()` scopes and `deactivate()` / `reactivate()` helpers. Do not replace controller, policy, request, or action-level lifecycle checks with the trait when a workflow needs explicit authorization, validation, stale-state protection, locking, redirects, or interface messages.
- Soft-delete-aware uniqueness uses partial indexes with `WHERE deleted_at IS NULL`. Deactivated records still reserve values unless the index excludes `deactivated_at`.
- Coordinate columns use decimal storage, `float` model casts, nullable PHPDoc, validation ranges, and database checks: latitude `-90..90`, longitude `-180..180`. Keep paired coordinate validation in requests when both values are required together.
- Decimal columns use string PHPDoc with `decimal:n` casts. Use the precision from sibling migrations and factories.
- Prunable temporary-code models should return a typed `Builder<static>` and keep expiration/used cleanup rules in `prunable()`.

### PHPDoc Coverage

Keep model PHPDoc useful for static analysis:

- Include scalar DB columns as `@property-read`.
- Include nullable values as `null|Type`.
- Include enum and value object casts using their class names.
- Include timestamps and soft-delete timestamps.
- Include relationship properties with `Collection<int, Model>` or nullable/single model types.

Do not add docblock entries for imagined relationships or columns.

### Test Suite Split

Use `references/tests/Unit/Models/README.md` for class-local contracts and `references/tests/Integration/Models/README.md` for the canonical persisted model boundary.

Unit model tests cover local class contracts such as traits, casts, defaults, and pure helpers. Integration model tests cover persisted domain behavior such as default-child selection, observer effects, slug stability, route key persistence, and `Workspace`-scoped system rules.

## Coverage Expectations

Read the live model, migration, factory, and sibling model files for the touched area. Cover behavior in the suite or reference that owns that surface.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not add relationship smoke tests just to prove framework wiring.

## Related References

- `references/app/Models/Concerns/README.md`
- `references/app/Models/World/README.md`
- `references/tests/Integration/Models/README.md`
- `references/tests/Unit/Models/README.md`
