# app/Http/Middleware

## Purpose

This reference defines conventions for middleware under `app/Http/Middleware`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `app/Http/Middleware` for request gating, Inertia shared props, and request-level behavior.

### Middleware Shape

- Keep middleware focused on request/response behavior.
- For Inertia sharing, return resource-shaped auth data consistently with the local Inertia middleware.
- Shared auth props are always shaped consistently. Guests receive explicit `null` values; authenticated actors receive resource-shaped actor data, and `Workspace` data is resource-shaped only when a current `Workspace` exists.
- Inertia modal support is provided by the frontend layout stack, not by current middleware state. Keep middleware concerned with shared props and request behavior.
- For access middleware, keep guest, non-authorized, and authorized behavior explicit.
- Access middleware should abort with the exact HTTP status used by the live middleware, such as `403` for access-denied branches.
- Middleware aliases, guest/auth redirects, API throttling, and web middleware append order are configured in the application bootstrap. Read the bootstrap middleware configuration before changing middleware names, aliases, or shared Inertia behavior.

### Access Middleware Example

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureExampleAccess
{
    /**
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(
            $request->user() === null || ! $request->user()->hasExampleAccess(),
            Response::HTTP_FORBIDDEN
        );

        return $next($request);
    }
}
```

### Inertia Shared Props Example

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inertia\Middleware;
use Override;

class ExampleInertiaRequests extends Middleware
{
    #[Override]
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => $this->authProperties($request),
        ];
    }

    /**
     * @return array{actor: null|JsonResource, workspace: null|JsonResource}
     */
    private function authProperties(Request $request): array
    {
        $actor = $request->user();

        return [
            'actor' => $actor?->toResource(),
            'workspace' => $actor?->currentWorkspace?->toResource(),
        ];
    }
}
```

### Test Mapping

- Middleware behavior is covered through `tests/Feature/Http/Middleware`.
- Define local test routes instead of relying on broad app routes.
- Assert Inertia shared props through `assertInertia`.
- Assert access middleware with guest, non-authorized, and authorized requests.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/tests/Feature/Http/Middleware/README.md`
- `references/tests/Pest.md`
