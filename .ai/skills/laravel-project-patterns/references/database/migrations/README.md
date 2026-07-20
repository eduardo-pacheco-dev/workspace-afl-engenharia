# Migrations and Schema

## Purpose

This reference defines project conventions for Laravel migrations and schema-backed columns.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use this reference when creating or changing Laravel migrations or schema-backed columns.

### Discovery

Before writing a migration:

```bash
rg --files database/migrations tests/migrations | sort
rg "function down|constrained\\(|foreign\\(|references\\(|cascadeOn|dropForeign" database/migrations tests/migrations
rg "foreignId\\(" database/migrations
```

When Laravel Boost is available, inspect the schema with `database-schema` summary first, then filtered details for affected tables. Confirm indexes and foreign keys in the live schema instead of inferring from code only.

### File Shape

Use this structure unless sibling migrations prove otherwise:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Tpetry\PostgresqlEnhanced\Schema\Blueprint;
use Tpetry\PostgresqlEnhanced\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('child_records', function (Blueprint $table): void {
            $table->id();
            $table->caseInsensitiveText('public_id')->unique();

            $table->foreignId('parent_record_id')->index();

            $table->timestamp('deactivated_at')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('name');

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE child_records
            ADD CONSTRAINT child_records_public_id_format_check
            CHECK (public_id ~* '^[a-z0-9]{10}$')
        SQL);
    }
};
```

### Local Schema Rules

- Include `declare(strict_types=1);`.
- Use anonymous classes: `return new class () extends Migration {`.
- Type migration methods and schema closures with `: void`.
- Omit `down()` when the migration set intentionally omits rollback methods.
- Put `id()` first.
- Add `caseInsensitiveText('public_id')->unique()` for routeable, interface-addressed, or API-exposed domain entities when following current domain-table migrations.
- Add a PostgreSQL `CHECK (public_id ~* '^[a-z0-9]{10}$')` constraint for new public-ID domain tables when sibling migrations do.
- Do not add `public_id` to framework, package, pivot, token, queue, cache, or one-off auth tables unless the table is routeable or publicly addressed.
- Add ownership/reference columns before descriptive fields.
- Use `foreignId('..._id')->index()` for relationship columns.
- Do not call `constrained()`, `foreign()`, `references()`, `cascadeOnDelete()`, or other database FK helpers unless the existing repository explicitly uses DB-level FKs.
- Use `timestamps()` consistently; add `softDeletes()` for entities whose deletes are reversible or whose uniqueness should ignore deleted rows.
- Prefer `timestamp(...)->nullable()` for nullable lifecycle timestamps such as `deactivated_at`.
- For pivot tables, include an `id()`, indexed relation columns, timestamps when sibling pivots do, and a compound `unique([...])` for the pair or tuple.
- For morph-backed package or token tables, follow the package-owned shape and keep relation columns indexed through `morphs(...)`.
- For nullable geographic coordinates, use `decimal(..., 10, 7)` and database range checks: latitude allows `NULL` or `BETWEEN -90 AND 90`; longitude allows `NULL` or `BETWEEN -180 AND 180`.
- For nullable column pairs that must be both `NULL` or both non-`NULL`, add a named PostgreSQL `CHECK` for that all-or-none invariant and prove both invalid directions through direct persistence tests.
- Use enum-backed string columns with explicit lengths for ISO-like values, for example `string('region_code', 2)` or `string('unit_code', 3)`.
- Use decimal precision intentionally, for example measurements as `decimal(..., 8, 4)` and amount bands as `decimal(..., 8, 2)`.
- Use database defaults only when sibling migrations do; otherwise mirror defaults on the model `$attributes` and factory.
- For soft-deleted domain tables with business uniqueness, prefer partial unique indexes scoped with `WHERE deleted_at IS NULL`. In current local usage, `active` index names mean non-soft-deleted; do not add `deactivated_at IS NULL` unless the live invariant explicitly excludes deactivated rows.

Pivot table pattern:

```php
Schema::create('actor_parent_record', function (Blueprint $table): void {
    $table->id();

    $table->foreignId('actor_id')->index();
    $table->foreignId('parent_record_id')->index();

    $table->timestamps();

    $table->unique(['actor_id', 'parent_record_id']);
});
```

### PostgreSQL Patterns

If the app uses `tpetry/laravel-postgresql-enhanced` or `citext`:

- Use `Tpetry\PostgresqlEnhanced\Schema\Blueprint` and facade only for enhanced column types such as `caseInsensitiveText`.
- Keep regular Laravel `Blueprint` and `Schema` for ordinary tables.
- Use `DB::statement(<<<'SQL' ... SQL);` for partial unique indexes.
- Keep index names explicit and descriptive, especially for partial unique indexes such as non-deleted email, non-deleted region, or soft-delete-aware normalized code constraints.
- Use `DB::statement(<<<'SQL' ... SQL);` for additive check constraints on existing tables when no column changes are needed.

Soft-delete-aware unique index pattern:

```php
DB::statement(<<<'SQL'
    CREATE UNIQUE INDEX child_records_active_name_unique
    ON child_records (parent_record_id, normalized_name)
    WHERE deleted_at IS NULL
SQL);
```

Soft-delete-aware default flag pattern:

```php
DB::statement(<<<'SQL'
    CREATE UNIQUE INDEX child_records_active_default_unique
    ON child_records (parent_record_id)
    WHERE deleted_at IS NULL AND is_default = true
SQL);
```

Nullable coordinate range check pattern:

```php
DB::statement(<<<'SQL'
    ALTER TABLE child_records
    ADD CONSTRAINT child_records_latitude_range_check
    CHECK (latitude IS NULL OR latitude BETWEEN -90 AND 90)
SQL);
```

Paired nullable-column check pattern:

```php
DB::statement(<<<'SQL'
    ALTER TABLE child_records
    ADD CONSTRAINT child_records_value_pair_check
    CHECK (
        (start_value IS NULL AND end_value IS NULL)
        OR (start_value IS NOT NULL AND end_value IS NOT NULL)
    )
SQL);
```

When adding or changing a partial unique index that enforces a business invariant, add focused coverage for direct persistence in the model integration suite so the new or changed database constraint is proven independently of controller validation.

When a database check constraint backs request validation, add the same direct persistence coverage with the constraint name, for example a model integration test expecting `QueryException` containing `child_records_latitude_range_check`.

### Anti-Patterns

- Do not add a generated `down()` just because Laravel docs show one.
- Do not add DB-level foreign key constraints in an app that uses indexed relation columns only.
- Do not rename existing columns or indexes for cleanup while adding unrelated schema.
- Do not make a new model routeable by slug unless sibling models and routes show slug route keys for that concept.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry. Database-backed uniqueness should normally have both request/controller validation coverage and direct persistence coverage for the database constraint when direct writes must be blocked.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/Models/README.md`
- `references/database/factories/README.md`
- `references/tests/Integration/Models/README.md`
