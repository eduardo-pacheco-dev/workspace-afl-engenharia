# app/Exceptions

## Purpose

This reference defines conventions for domain exceptions under `app/Exceptions`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `app/Exceptions` for domain exceptions that represent failed application invariants, retry exhaustion, or action-owned guards that controllers map back to validation.

### Exception Shape

- Prefer static named constructors for domain-specific exception messages.
- Return `self` from named constructors.
- Build messages with `sprintf(...)` when they include runtime counts or values.
- Keep messages precise because tests may assert them.
- When a controller always maps an action exception to one fixed validation field, keep the exception simple and let the controller own the field/message mapping.
- When the mapped validation field varies by failure branch, let the exception carry a public readonly field name and message.
- Do not use a custom exception when validation errors or framework exceptions are the existing pattern.

Fixed-field controller-mapped exception pattern:

```php
final class CannotDeleteParentRecord extends Exception
{
    public static function becauseItHasDependencies(): self
    {
        return new self('Cannot delete a parent record with dependent records.');
    }
}
```

Branch-specific validation-mapping exception pattern:

```php
final class CannotCreateLeafRecord extends Exception
{
    public function __construct(
        public readonly string $field,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function becauseRangeOverlaps(): self
    {
        return new self('start_value', 'The selected range overlaps another range.');
    }
}
```

Retry-limit exception pattern:

```php
final class CannotGenerateChildRecordCode extends Exception
{
    public static function maxAttempts(int $attempts): self
    {
        return new self(
            sprintf('Unable to generate a unique child record code after %d attempts.', $attempts)
        );
    }
}
```

### Tests

- Assert exceptions through the action, model, or feature path that raises them.
- Use exact messages when the message is part of the contract.
- For retry-limit exceptions, assert the max-attempt message from the owning action test.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/Actions/README.md`
- `references/app/Http/Controllers/README.md`
- `references/tests/Integration/Actions/README.md`
