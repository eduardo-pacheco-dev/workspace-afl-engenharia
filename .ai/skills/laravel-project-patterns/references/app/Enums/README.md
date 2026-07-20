# app/Enums

## Purpose

This reference defines conventions for enums under `app/Enums`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `app/Enums` for backed enums and enum helpers used by validation, resources, factories, forms, model casts, flash payloads, and generated-value configuration.

### Enum Shape

- Use TitleCase case names.
- Use `ArchTech\Enums\Values` when callers need `values()`.
- Use `InvokableCases` when code calls `Enum::CaseName()`, and keep matching `@method` PHPDoc for the returned primitive: the backing value for backed enums, or the case name string for pure enums.
- Use `Comparable` on option-style enums that siblings compare directly.
- Use `Options` plus the project enum metadata attribute when the enum exposes translated `{ label, value }` options for UI/form contracts.
- Keep shared option helpers under enum concerns and metadata support under enum metadata properties.
- Keep enum helper methods deterministic and side-effect free.
- When an enum exposes labels, values, options, alphabets, variants, or units, treat that output as application contract.
- Keep configured alphabets explicit strings on the enum when actions pass them into NanoID generation.

### Simple Values Enum Example

```php
<?php

declare(strict_types=1);

namespace App\Enums;

use ArchTech\Enums\Values;

enum ExampleVariant: string
{
    use Values;

    case Accent = 'accent';
    case Default = 'default';
    case Success = 'success';
}
```

### Translated Option Enum Example

```php
<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\Options;
use App\Enums\Metadata\ExampleOptionLabelKey;
use ArchTech\Enums\Comparable;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Values;

/**
 * @method static string Active()
 * @method static string Archived()
 * @method static string Draft()
 */
enum ExampleStatus: string
{
    use Comparable;
    use InvokableCases;
    use Options;
    use Values;

    #[ExampleOptionLabelKey('example_status.active')]
    case Active = 'active';

    #[ExampleOptionLabelKey('example_status.archived')]
    case Archived = 'archived';

    #[ExampleOptionLabelKey('example_status.draft')]
    case Draft = 'draft';
}
```

Option concern and metadata property shape:

```php
<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

use function App\__;

use App\Enums\MetaProperties\TranslationKey;
use ArchTech\Enums\Meta\Meta;
use ArchTech\Enums\Metadata;
use BackedEnum;

/**
 * @method string translationKey()
 */
#[Meta(TranslationKey::class)]
trait Options
{
    use Metadata;

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public static function options(): array
    {
        /** @var array<int, array{label: string, value: string}> */
        return collect(static::cases())->map(fn (BackedEnum $case): array => [
            'label' => __($case->translationKey()),
            'value' => $case->value,
        ])->all();
    }
}
```

### Helper Enum Example

```php
<?php

declare(strict_types=1);

namespace App\Enums;

use ArchTech\Enums\Comparable;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Values;

/**
 * @method static string Alphanumeric()
 * @method static string Letters()
 * @method static string Numbers()
 */
enum ExampleAlphabetType: string
{
    use Comparable;
    use InvokableCases;
    use Values;

    case Alphanumeric = 'alphanumeric';
    case Letters = 'letters';
    case Numbers = 'numbers';

    public function alphabet(): string
    {
        return match($this) {
            self::Alphanumeric => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            self::Letters => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            self::Numbers => '0123456789',
        };
    }

    public function label(): string
    {
        return match($this) {
            self::Alphanumeric => 'Alphanumeric',
            self::Letters => 'Letters only (A-Z)',
            self::Numbers => 'Numbers only (0-9)',
        };
    }
}
```

### Test Mapping

- Pure enum contracts are covered through `tests/Unit/Enums`.
- Every enum with `values()` should have an exact values test.
- When changing enum helper methods beyond `values()`, add focused assertions for the changed contract.
- When changing `Options`, translated option enums, or metadata properties, add options/translation-key assertions in the owning unit test or preserve the controller feature test that exposes `options()` as a prop contract. Current baseline enum tests may only assert `values()` when controller tests already protect the exposed option arrays.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/tests/Unit/Enums/README.md`
- `references/tests/Feature/Http/Controllers/README.md`
- `references/app/functions.php.md`
