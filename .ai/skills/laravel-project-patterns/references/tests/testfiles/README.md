# tests/testfiles

## Purpose

This reference defines conventions for static fixtures under `tests/testfiles`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/testfiles` for static fixtures needed by command, file, import/export, media, or parsing tests.

### Fixture Rules

- Keep fixtures small.
- Prefer deterministic text or tiny binary fixtures over generated large files.
- Name fixtures by their purpose and extension.
- Load fixtures through framework file helpers or explicit paths from the test.

### Command Test Pattern

For commands that download and extract files, store the compressed sample here and fake the HTTP response with its bytes.

```php
use function Pest\Laravel\artisan;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

it('downloads and extracts the reference data database', function (): void {
    $databasePath = storage_path('framework/testing/reference-data.sqlite3');

    Config::set('database.connections.reference_data.database', $databasePath);

    Http::fake([
        Config::string('reference-data-database.download_url') => Http::response(
            File::get(base_path('tests/testfiles/reference-data.sqlite3.gz'))
        ),
    ]);

    artisan('app:download-reference-data-database')->assertSuccessful();

    $database = new PDO('sqlite:'.$databasePath);

    expect($database->query('PRAGMA integrity_check')->fetchColumn())->toBe('ok');

    File::delete($databasePath);
});

it('fails when the downloaded reference data database is malformed', function (): void {
    $databasePath = storage_path('framework/testing/invalid-reference-data.sqlite3');

    Config::set('database.connections.reference_data.database', $databasePath);

    Http::fake([
        Config::string('reference-data-database.download_url') => Http::response(
            File::get(base_path('tests/testfiles/malformed-reference-data.sqlite3.gz'))
        ),
    ]);

    expect(fn () => artisan('app:download-reference-data-database'))
        ->toThrow(PDOException::class);
});
```

### Do Not

- Do not write runtime output into `tests/testfiles`.
- Do not add large fixtures without explicit need.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/Console/Commands/README.md`
- `references/tests/Feature/Console/README.md`
