# app/functions.php

## Purpose

This reference defines project conventions for tiny global helpers in `app/functions.php`.

## When To Use

Use this reference when creating or changing a global helper already established in `app/functions.php`.

## Required Pattern

Use `app/functions.php` only for tiny global helpers that are already established by the codebase.

### Helper Shape

- Keep helpers minimal and typed.
- Keep the `App\__(...)` wrapper around `trans(...)` so callers receive a narrowed `string` return type.
- Document translation replacement arrays with `array<string, null|bool|float|int|string>`.
- Keep `toast(...)` payloads aligned with the shared redirect macro.
- Store toast flash data under the shared flash-key enum's toast case and serialize variants with the variant enum's `value`.
- Convert toast timeouts from seconds to milliseconds.
- Use `array_filter(..., fn (null|int|string $value): bool => $value !== null)` so falsey but non-null values are preserved.
- Do not add broad utility functions here when a class or framework helper would be clearer.
- Preserve existing helper contracts for translations and toast flash data.

Toast payload pattern:

```php
function toast(
    string $title,
    null|string $description = null,
    ExampleNoticeVariant $variant = ExampleNoticeVariant::Success,
    int $timeout = 5
): void {
    Inertia::flash(ExampleNoticeKey::Toast(), array_filter([
        'description' => $description,
        'timeout' => $timeout * 1000,
        'title' => $title,
        'variant' => $variant->value,
    ], fn (null|int|string $value): bool => $value !== null));
}
```

Test assertion pattern:

```php
TestResponse::macro('assertToast', function (
    string $title,
    null|string $description = null,
    ExampleNoticeVariant $variant = ExampleNoticeVariant::Success,
    int $timeout = 5
): TestResponse {
    return $this->assertSessionHas('inertia.flash_data.toast', array_filter([
        'description' => $description,
        'timeout' => $timeout * 1000,
        'title' => $title,
        'variant' => $variant->value,
    ], static fn (null|int|string $value): bool => $value !== null));
});
```

### Tests

- Test helper behavior through the feature path that consumes it unless the helper grows standalone branching.
- If the helper and redirect macro diverge, update both or extract the shared payload builder first.

## Coverage Expectations

Read the live file and the macro/helper consumers before changing a helper contract. Cover behavior through the consuming feature path unless standalone branching is introduced.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not add unrelated utility helpers.

## Related References

- `references/app/README.md`
