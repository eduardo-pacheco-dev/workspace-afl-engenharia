# app/Console/Commands

## Purpose

This reference defines conventions for Artisan commands under `app/Console/Commands`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `app/Console/Commands` for application infrastructure, scheduled or manually run maintenance, and data ingestion.

### Command Shape

- Use command attributes for signature and description when sibling commands do.
- Implement `handle(): int`.
- Return `Command::SUCCESS` or the appropriate framework command code.
- Read config with typed config helpers.
- Use framework HTTP/file abstractions and package helpers rather than shelling out.
- For downloads, use `Http::retry(...)->sink(...)->get(...)->throw()` and a `TemporaryDirectory` when the file should not persist after the command.
- Use `Safe\*` functions for filesystem transforms when the local command already depends on exception-on-failure behavior.
- Write operator-facing command output through `$this->components`.

Download-style command shape:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use function Safe\copy;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PDO;
use RuntimeException;
use Spatie\TemporaryDirectory\TemporaryDirectory;

#[Description('Download the reference data SQLite database.')]
#[Signature('app:download-reference-data-database')]
class DownloadReferenceDataDatabaseCommand extends Command
{
    public function handle(): int
    {
        $temporaryDirectory = new TemporaryDirectory()
            ->deleteWhenDestroyed()
            ->create();

        Http::retry(3, 500)
            ->sink($temporaryDirectory->path('reference-data.sqlite3.gz'))
            ->get(Config::string('reference-data-database.download_url'))
            ->throw();

        copy(
            sprintf('compress.zlib://%s', $temporaryDirectory->path('reference-data.sqlite3.gz')),
            Config::string('database.connections.reference_data.database')
        );

        $pdo = new PDO('sqlite:'.Config::string('database.connections.reference_data.database'));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $integrityCheck = $pdo->query('PRAGMA integrity_check');

        throw_if(
            $integrityCheck === false || $integrityCheck->fetchColumn() !== 'ok',
            RuntimeException::class,
            'Reference data SQLite database failed integrity check.'
        );

        $this->components->info('Reference data SQLite database downloaded successfully.');

        return Command::SUCCESS;
    }
}
```

### Test Mapping

- Command behavior is covered through `tests/Feature/Console` when the command is the entrypoint being changed.
- Fake HTTP responses. Use real fixtures, temp paths, or storage assertions when the command contract is file extraction, downloads, or persisted filesystem side effects.
- Use Pest's `artisan('command:name')->assertSuccessful()`.
- Assert the actual file/database side effect the command owns.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/tests/Feature/Console/README.md`
- `references/project/README.md`
