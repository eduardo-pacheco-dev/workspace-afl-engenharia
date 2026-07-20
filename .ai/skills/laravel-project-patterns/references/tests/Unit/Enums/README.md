# tests/Unit/Enums

## Purpose

This reference defines conventions for `tests/Unit/Enums`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/Unit/Enums/<Enum>Test.php` for enum behavior that can be proven without persistence or HTTP.

### File Shape

- `declare(strict_types=1);`
- Import the enum under test.
- Use focused `it(...)` blocks.
- When a file covers multiple enum methods, order trait-provided method tests first, then enum-defined method tests. Within each group, order `it(...)` blocks alphabetically by the method name they cover.
- Prefer exact arrays with `toEqual([...])` for values.

### Values Tests

Every enum with a `values()` contract should have a test named like:

```php
it('defines available values', function (): void {
    expect(SomeEnum::values())->toEqual([
        'first',
        'second',
    ]);
});
```

### Helper Method Tests

When the enum exposes helper methods, keep the trait-provided `values()` test first, then add exact helper assertions with datasets:

```php
it('defines alphabets', function (ExampleAlphabetType $alphabetType, string $alphabet): void {
    expect($alphabetType->alphabet())->toBe($alphabet);
})->with([
    'alphanumeric' => [ExampleAlphabetType::Alphanumeric, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'],
    'letters' => [ExampleAlphabetType::Letters, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'],
    'numbers' => [ExampleAlphabetType::Numbers, '0123456789'],
]);

it('defines labels', function (ExampleAlphabetType $alphabetType, string $label): void {
    expect($alphabetType->label())->toBe($label);
})->with([
    'alphanumeric' => [ExampleAlphabetType::Alphanumeric, 'Alphanumeric'],
    'letters' => [ExampleAlphabetType::Letters, 'Letters only (A-Z)'],
    'numbers' => [ExampleAlphabetType::Numbers, 'Numbers only (0-9)'],
]);
```

### Additional Behavior

Add more `it(...)` blocks when the enum has methods beyond `values()`, for example:

- label generation;
- alphabet/string expansion;
- option list formatting;
- conversion helpers.

Prefer direct enum method calls inside `expect(...)` for pure enum behavior unless the intermediate value is reused or materially improves clarity.

Assert exact outputs, not only types, because enum changes are API changes for forms, validation, and resources.

### Do Not

- Do not use factories.
- Do not hit routes.
- Do not assert translated labels unless the enum method itself owns the translation output.
- Do not duplicate controller feature-test option-prop assertions unless the enum, `Options` concern, or metadata property behavior changed.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/Enums/README.md`
- `references/tests/Feature/Http/Controllers/README.md`
