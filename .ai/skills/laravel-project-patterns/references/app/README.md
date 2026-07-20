# Application Patterns

## Purpose

This reference defines project conventions for the Laravel application layer under `app/`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use this reference after reading the exact sibling files for the surface being changed. These patterns are drawn from the full `app/` tree and should guide new code without freezing the application into one narrow module shape.

### Global PHP Shape

- Every PHP file in the application uses `declare(strict_types=1);`.
- Prefer explicit imports, explicit return types, and typed parameters.
- Always use curly braces for control structures, even when the body has one statement.
- Prefer constructor property promotion for injected dependencies and promoted values. Do not keep empty zero-argument constructors unless the constructor is private.
- Use `#[Override]` when overriding framework methods or properties where the local code already does.
- Use PHPDoc blocks for array shapes, Eloquent relationship generics, resource model properties, and exception annotations.
- Keep enum cases in TitleCase and use local enum traits/attributes when sibling enums do.
- Prefer small methods with descriptive names over inline notes.

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\CannotGenerateExampleValue;
use App\Models\Workspace;
use ExamplePackage\Client;

class GenerateExampleValue
{
    public const int MAX_RETRY_ATTEMPTS = 20;

    public function __construct(private readonly Client $client)
    {
    }

    /**
     * @return array{code: string, attempts: int}
     */
    public function handle(Workspace $workspace): array
    {
        $attempts = 0;

        while ($attempts < self::MAX_RETRY_ATTEMPTS) {
            $code = $this->client->formattedId();

            ++$attempts;

            if (! $this->codeExists($workspace, $code)) {
                return [
                    'attempts' => $attempts,
                    'code' => $code,
                ];
            }
        }

        throw CannotGenerateExampleValue::maxAttempts($attempts);
    }

    private function codeExists(Workspace $workspace, string $code): bool
    {
        return $workspace->exampleRecords()
            ->where('normalized_code', $code)
            ->exists();
    }
}
```

### Controllers

- Web controllers typically implement `HasMiddleware` and return a static `middleware(): array` with `new Middleware('can:...', only: [...])` for authorization.
- Web CRUD actions return Inertia responses for read/create/edit/index/show and redirects for mutations.
- Use `Inertia::render('pages/...', [...])` for pages; keep resources and option lists shaped in the controller exactly like siblings.
- Use Eloquent resources with `->toResource()` and collections with `->toResourceCollection()` where sibling controllers do.
- Mutations usually create, update, or delete through the owning relationship to preserve `Workspace` ownership.
- Redirect with named routes via `to_route(...)`, then attach toasts with the shared `toast()` macro.
- When a mutation delegates persistence to an action with a Data input, construct the input from validated request data at the controller boundary and pass it to the injected action.
- For delegated top-level mutations on an already-bound model, pass the model and input the action actually needs. Do not pass the route `Workspace` or parent only so the action can re-query ownership already enforced by scoped bindings and policy middleware.
- For delegated nested-child mutations, pass the parent and child because the action owns the parent/child invariant beyond the HTTP boundary.
- Query list pages with local ordering and pagination conventions such as `latest('id')->paginate()`.
- Preserve route-model binding semantics from `Route::scopeBindings()`. If an ancestor or child does not belong to the route chain, feature tests should assert `404`, not manually authorize it.
- For nested controllers, include route parameters in the same order used by the route name and sibling tests: `Workspace` or top-level parent first, then each ancestor, then the leaf model.
- For fields submitted as public IDs but stored as internal IDs, validate against `public_id` and convert after validation at the controller boundary when the live controller pattern does that.
- API controllers use `getJson()`/`postJson()` tests, resource responses, `JsonResponse`, validation exceptions, HTTP fakes, and token assertions as appropriate.

### Form Requests

- Form requests define typed `rules(): array` methods with precise PHPDoc array shapes.
- Use `Rule::enum(...)`, `Rule::unique(...)->where(...)->withoutTrashed()`, and `ignore($model)` for scoped uniqueness.
- Use route parameters through local conventions: route-parameter attributes for typed route models when validation scopes to a bound parent, and helper methods when the request needs repeated access to a bound model.
- Use `prepareForValidation()` for derived input, PATCH-style partial updates, and relationship inference. Do not use it just to coerce a public ID into an integer foreign key when the controller can resolve the validated public ID before persistence.
- Do not add Form Request `input()` or `payload()` helpers when a Data input can be built from `$request->validated()` in the controller.
- Use `after(): array` for request-owned cross-field or domain validation that needs the hydrated model graph. If the guard needs action-owned transactional state, locks, or dependent-record checks, keep it in the action and map the action exception at the controller boundary when that is the sibling pattern.
- Store requests usually use `required`; update requests often use `sometimes|required`.
- Feature tests should assert exact validation messages when sibling tests do, especially custom domain messages.

### Resources

- Resources extend `JsonResource`, add `@property Model $resource`, and implement `toArray(Request $request): array`.
- The serialized `id` is usually the model public id, not the internal integer key.
- Dates are serialized in resource tests with `toJSON()`.
- Decimal casts serialize as strings. Enum values serialize as backing values.
- Phone number/value-object output should match the resource method exactly, such as E164 or national formatting.
- Conditional fields should be tested both present and omitted when the resource uses conditional helpers.
- Static caches in resources are an existing local pattern for reference-data tables; do not expand them casually, and account for Octane if the data can change during a worker lifetime.

### Models And Policies

- Models rely on global unguarding; do not add `$fillable` or `$guarded` when siblings omit them.
- For normal app-owned persisted attribute mutations, use `$model->update([...])`. Do not use `forceFill(...)->save()` just to bypass mass assignment because models are globally unguarded during application boot.
- Use typed relationship methods with Eloquent generic return PHPDoc.
- Public-id route keys are provided by the local trait unless a model overrides `getRouteKeyName()` for a different public key such as a slug.
- Public ID finder helpers return models and throw framework 404s. Use them at controller boundaries when converting validated public IDs to persisted internal IDs.
- Soft-deletable models should have unit trait/cast coverage and integration tests only for project/system behavior that depends on saved records.
- `Workspace` authorization is usually expressed through actor ownership or membership checks on the relevant `Workspace` model.
- Policies are commonly validated through controller feature tests instead of standalone policy tests, unless the policy itself has isolated logic worth testing.

### Actions, Commands, Listeners, Notifications, Providers, Support

- Actions are container-resolved classes with constructor injection and a public `handle(...)` entrypoint; integration tests resolve the action from the container and mock injected collaborators where needed.
- Actions may accept typed Data inputs when they own persistence-ready transformation, `Optional` omission, or model-default behavior. Use the input's transformed array for Eloquent writes.
- Actions receive only their business inputs and do not accept route hierarchy solely to duplicate entrypoint ownership checks. Query fresh state only for an action-owned guard, lock, or required relationship read; a create action may accept its direct business parent.
- Commands use attribute-based signatures/descriptions, typed `handle(): int`, faked HTTP/filesystem dependencies in tests, and explicit command success assertions.
- Listeners own package/framework event side effects and should have integration listener tests that trigger the real event path. Observers are not a live app pattern yet; if one is added, prove model lifecycle side effects through persisted model tests.
- Notifications implement the framework queueing pattern used locally and are normally asserted through the feature or action that sends them.
- Providers configure application-wide behavior; avoid changing provider boot logic unless the blast radius is intentional and covered by architecture, feature, or integration tests.
- Support classes need integration tests that fake the external boundary they touch, such as storage, uploaded files, HTTP clients, SDKs, or framework package hooks.

### Test Support

- Global Pest setup freezes time and disables Vite for Unit, Integration, and Feature suites.
- Use the shared login helper when authentication is needed.
- Use existing test support models/migrations for generic trait or package-support behavior instead of creating application-only fixtures.
- `Http::preventStrayRequests()` is active, so every external request in a test must be faked or mocked.
- Web controller tests should be compared against sibling nested resources before adding or deleting cases; the deepest sibling files are the best source for 404 boundary coverage.

For model integration test boundaries, load `references/tests/Integration/Models/README.md` instead of repeating that policy in this broad application overview.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/Actions/README.md`
- `references/app/Http/Controllers/README.md`
- `references/app/Http/Requests/README.md`
- `references/app/Models/README.md`
- `references/app/Providers/README.md`
- `references/app/Support/README.md`
- `references/tests/README.md`
