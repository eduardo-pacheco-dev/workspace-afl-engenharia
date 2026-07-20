# tests/migrations

## Purpose

This reference defines conventions for test-only migrations.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/migrations` for schema required only by test support models or generic package/trait tests.

### When To Add

Add a test migration when:

- a test support model needs a table;
- a generic trait needs a model/table without coupling to application models;
- package integration needs a minimal schema fixture.

### Style

- Follow the same migration style as application migrations unless the test-only schema has a reason to differ.
- Keep columns minimal and directly tied to the support model or concern under test.
- Add generic concern columns such as `deactivated_at` only when a support model trait needs them, and keep them close to the related support-model schema rather than adding application-domain fixtures.
- These migrations are included by the base `TestCase` during fresh migrations.

### Example

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
        Schema::create('example_models', function (Blueprint $table): void {
            $table->id();
            $table->caseInsensitiveText('public_id')->unique();

            $table->timestamp('deactivated_at')->nullable();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE example_models
            ADD CONSTRAINT example_models_public_id_format_check
            CHECK (public_id ~* '^[a-z0-9]{10}$')
        SQL);
    }
};
```

### Do Not

- Do not put application domain migrations here.
- Do not use test migrations to bypass missing application schema coverage.
- Do not add broad fixture tables when a small support model is enough.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/tests/TestCase.md`
- `references/tests/Support/Models/README.md`
- `references/tests/Integration/Models/Concerns/README.md`
- `references/tests/Feature/Models/Concerns/README.md`
