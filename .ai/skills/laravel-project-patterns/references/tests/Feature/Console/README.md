# tests/Feature/Console

## Purpose

This reference defines conventions for feature tests under `tests/Feature/Console`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/Feature/Console/<Command>Test.php` for Artisan commands that interact with framework services, files, HTTP, config, or storage.

### File Shape

- Import `Pest\Laravel\artisan`.
- Fake HTTP responses with `Http::fake()`.
- Override config values with `Config::set(...)` when the command reads config.
- Use framework facades for file assertions when sibling tests do.
- Assert command success with `artisan('command:name')->assertSuccessful()`.

### Download/Extract Command Pattern

For commands that download files:

- point output to a test storage path;
- fake the exact configured URL;
- serve bytes from `tests/testfiles` when a fixture exists;
- assert the extracted/written file contents;
- clean up only the file the test created.

```php
<?php

declare(strict_types=1);

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

- Do not make live HTTP calls.
- Do not rely on the developer's local database/file state except through configured test paths.
- Do not start long-running services from a feature command test.

## Coverage Expectations

Read the command source file being tested, compare it with sibling commands, and cover only the behavior specific to that command. Do not add tests merely to match coverage patterns in other test files.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/Console/Commands/README.md`
- `references/project/README.md`
