# tests/Feature/Models/Concerns

## Purpose

This reference defines conventions for route-binding concern tests under `tests/Feature/Models/Concerns`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/Feature/Models/Concerns/<Concern>Test.php` for concern behavior that only exists when routing or HTTP middleware is involved.

### Route Binding Pattern

For public route keys:

- create a test support model;
- register a local route with `SubstituteBindings` middleware;
- type-hint the model in the route closure;
- assert the internal id does not resolve;
- assert the public id does resolve.

Expected shape:

```php
Route::middleware(SubstituteBindings::class)
    ->get('/_test/{model}', function (ExampleModel $model): void {
    });

get('/_test/'.$model->id)->assertNotFound();
get('/_test/'.$model->public_id)->assertOk();
```

### Split From Integration Concerns

Use `tests/Integration/Models/Concerns` for generated ids and finder methods. Use this path only for HTTP route model binding.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/Models/Concerns/README.md`
- `references/tests/Integration/Models/Concerns/README.md`
