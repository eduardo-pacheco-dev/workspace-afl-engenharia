# app/Policies

## Purpose

This reference defines conventions for policies under `app/Policies`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `app/Policies` for `Workspace` ownership authorization, nested resource access decisions, lifecycle authorization, and not-found denial for inconsistent ownership graphs.

### Policy Shape

- Keep policy methods small and explicit.
- Prefer membership or ownership checks through the relevant `Workspace` model.
- For nested models, authorize against the owning `Workspace` through the relationship chain.
- Use parent-aware methods such as `create(Actor $actor, ParentRecord $parentRecord)` and `viewAny(Actor $actor, ParentRecord $parentRecord)` consistently with siblings.
- Deeply nested route policies may keep the full route ancestor signature required by controller policy middleware, then derive the authorization decision from the direct owner chain. Do not add unrelated outer-`Workspace` checks solely because the method receives an outer route ancestor.
- A top-level `Workspace` policy may allow `create(Actor $actor)` for any authenticated actor by returning `true`; the route/controller test still owns the unauthenticated case.
- Return booleans unless the policy must hide an inconsistent denormalized ownership graph; in that case return `Response::denyAsNotFound()`.
- For denormalized ownership, verify the direct parent relationship and the stored `Workspace` agree before returning an authorization result.
- For lifecycle methods, authorize both ownership and the current active/deactivated state when the policy owns that state boundary.

### Top-Level Workspace Policy Example

```php
<?php

declare(strict_types=1);

namespace App\Policies;

class WorkspacePolicy
{
    public function create(Actor $actor): bool
    {
        return true;
    }

    public function update(Actor $actor, Workspace $workspace): bool
    {
        return $actor->belongsToWorkspace($workspace);
    }

    public function view(Actor $actor, Workspace $workspace): bool
    {
        return $actor->belongsToWorkspace($workspace);
    }
}
```

### Policy Example

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\Response;

class ChildRecordPolicy
{
    public function create(Actor $actor, ParentRecord $parentRecord): bool
    {
        return $actor->belongsToWorkspace($parentRecord->workspace)
            && $parentRecord->isActive();
    }

    public function update(Actor $actor, ChildRecord $childRecord): bool|Response
    {
        if ($childRecord->workspace_id !== $childRecord->parentRecord->workspace_id) {
            return Response::denyAsNotFound();
        }

        return $actor->belongsToWorkspace($childRecord->parentRecord->workspace)
            && $childRecord->parentRecord->isActive();
    }

    public function view(Actor $actor, ChildRecord $childRecord): bool|Response
    {
        if ($childRecord->workspace_id !== $childRecord->parentRecord->workspace_id) {
            return Response::denyAsNotFound();
        }

        return $actor->belongsToWorkspace($childRecord->parentRecord->workspace);
    }

    public function viewAny(Actor $actor, ParentRecord $parentRecord): bool
    {
        return $actor->belongsToWorkspace($parentRecord->workspace);
    }
}
```

### Tests

- Policy behavior is usually covered through controller feature tests.
- Assert `403` when bindings resolve and policy denies.
- Assert `404` when scoped route binding fails before policy execution.
- Assert `404` for policy-level `denyAsNotFound()` cases where an otherwise bound record has inconsistent ownership.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
- `references/tests/Feature/Http/Controllers/route-patterns.md`
