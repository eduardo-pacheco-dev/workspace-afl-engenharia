# Controller Feature Tests

## Purpose

This reference defines conventions for controller feature tests under `tests/Feature/Http/Controllers/**`.

## When To Use

Use this reference when the touched test is an HTTP controller feature test, including web/session controllers and JSON API controllers. Use it when the file contains controller action groups such as `describe('index')`, `describe('store')`, or `describe('update')`, route helpers, scoped route parameters, authorization assertions, Inertia assertions, JSON assertions, or request validation assertions.

Do not use this reference for model/unit tests, action integration tests, console tests, support tests, or non-HTTP behavior.

## Required Pattern

### Quick Start

```bash
php artisan make:test --pest Http/Controllers/<Name>ControllerTest --no-interaction
php artisan test --compact tests/Feature/Http/Controllers/<Name>ControllerTest.php
php artisan test --compact --filter="<test name>"
```

### Decision Workflow

1. Inspect the route definition, controller action, form request, policy, resource, and nearest sibling tests.
2. Choose the transport: web/session or JSON API.
3. Determine route shape, route parameter keys, public route keys, and every scoped-binding boundary.
4. Apply the action order from the matching action reference:
   - authentication;
   - authorization (`403`);
   - route binding and ownership mismatch (`404`) from outer ancestor to direct parent to leaf;
   - soft-deleted ancestor or leaf `404` beside the boundary it belongs to;
   - lifecycle or state guards that stop before validation;
   - validation datasets and named validation cases;
   - delegated action invocation or controller-owned persistence;
   - redirect/toast, Inertia, JSON, database, notification, token, or other side-effect assertions.
5. Keep only cases the live controller contract can reach, but do not remove controller entry-point tests only because action integration tests already cover internal guards.

### Central Rules

- Controller feature tests are entry-point tests. They remain required for authentication, authorization, scoped binding, request validation, action invocation, request-to-input mapping, redirects/toasts, Inertia/JSON contracts, and exception-to-validation mapping.
- Action integration coverage does not replace controller entry-point coverage. If a routed controller can reach a boundary, guard, validation path, mapped action exception, redirect, toast, or response contract, keep the controller test for that entry point even when the same domain condition is fully proven in `tests/Integration/Actions`.
- Form Requests own HTTP input shape, request normalization, scoped `exists`/`unique`, and request-safe cross-field validation.
- Actions own transactional state, locks, dependent-record checks, and race-sensitive domain guards.
- When the controller catches an action exception and maps it to validation, the controller test mocks the action and asserts the mapped validation error. The matching action integration test owns the real guard, transaction, lock, and persistence behavior.
- Name the test after the observable rejected behavior with `rejects` or `prevents`, not after the controller's exception-mapping implementation.
- Inertia assertions are backend response contracts: component names, serialized props, public IDs, redirects, and flash/toast output. They are not browser behavior tests.
- API assertions must assert exact validation messages and the JSON contract, not just status codes.
- Route parameters should use the public route key shape exposed by route binding. Internal integer IDs belong only at persistence assertions after validation or controller resolution.

### Observed Order

Use this global file order when a resource controller exposes all actions:

1. `create`
2. `destroy`
3. `edit`
4. `index`
5. `show`
6. `store`
7. `update`

Inside each action block, use failure-to-success order:

1. unauthenticated request;
2. authorization/current-`Workspace` `403`;
3. route-model binding and scoped ownership `404` from outer ancestor to leaf;
4. soft-deleted ancestor or leaf `404`;
5. lifecycle/state guard if it stops the request before validation or the action;
6. validation datasets and named validation cases;
7. delegated action invocation or controller-owned persistence;
8. primary success response;
9. extra success/list variants.

Invokable controllers may stay flat when sibling files are flat, but the same order still applies.

When restoring or adding controller entry-point tests, place each case in this action and layer order immediately. Do not append restored cases at the end of the file, group them by implementation change, or move them out of the route/action block they prove.

### Route Shapes

Use `route-patterns.md` for route examples and parameter composition.

| Shape                       | Applies to                                        | Required coverage                                                                                                                  |
| --------------------------- | ------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| Settings or singleton route | one page or one mutation under `workspaces.*`     | auth, `403`, response/mutation contract                                                                                            |
| Two-resource route chain    | `workspaces.parent-records.*`                     | `Workspace` auth/authorization, leaf `Workspace` mismatch, soft-deleted leaf, page/list/mutation contract                          |
| Three-resource route chain  | `workspaces.parent-records.children.*`            | all two-resource checks plus parent mismatch, child direct-parent mismatch, child `Workspace` mismatch, soft-deleted parent/child  |
| Four-resource route chain   | `workspaces.parent-records.children.leaves.*`     | outer parent, middle child, and leaf boundaries, including same-`Workspace` wrong-parent graphs and redundant ownership mismatches |
| Invokable nested mutation   | `workspaces.parent-records.children.make-default` | auth, `403`, parent/child `404`, side effect                                                                                       |
| Public JSON endpoint        | public JSON session or access-code endpoints      | validation first, domain failures, success JSON and side effects                                                                   |
| Protected JSON endpoint     | authenticated API endpoint                        | guest `401`, authenticated success, public-ID JSON                                                                                 |

### Controller-Owned Domain Rejection Example

```php
it('rejects updating a child record when its parent is inactive', function (): void {
    $childRecord = ChildRecord::factory()->createOne();

    login(workspace: $childRecord->parentRecord->workspace);

    mock(UpdateChildRecord::class)
        ->shouldReceive('handle')
        ->once()
        ->withArgs(fn (
            ParentRecord $parentRecord,
            ChildRecord $childRecordArgument,
            ExampleInput $input
        ): bool => $parentRecord->is($childRecord->parentRecord)
            && $childRecordArgument->is($childRecord)
            && $input->name === 'Updated')
        ->andThrow(CannotUpdateChildRecord::becauseParentIsInactive());

    $response = patch(route('workspaces.parent-records.children.update', [
        'workspace' => $childRecord->parentRecord->workspace,
        'parent_record' => $childRecord->parentRecord,
        'child_record' => $childRecord,
    ]), [
        'name' => 'Updated',
    ]);

    $response->assertRedirectBackWithErrors([
        'parent_record' => 'The selected parent record is not active.',
    ]);
});
```

This proves only the HTTP exception-to-validation mapping. The action integration test proves the lock, transaction, guard condition, and persisted result.

### Delegated Mutation Boundaries

- Mocked actions receive only the models and inputs that are business inputs to the operation. Do not pass route hierarchy only to re-check ownership already enforced by scoped bindings and policy.
- `withArgs(...)` callbacks must return booleans. Do not put Pest `expect()` chains inside Mockery matchers.
- For create/store mocks that need a redirect route key, return a persisted factory model with only required relationships. Do not set generated route keys unless the literal value is asserted.
- For partial update mocks, assert submitted fields and request-normalized fields only where relevant. Defaults, `Optional`, nullable clearing, and side effects belong in action integration tests unless the controller owns them.

### Baseline Assertions by Transport

Web/session:

- unauthenticated request -> `assertRedirectToRoute('login')`;
- validation failure -> `assertRedirectBackWithErrors([...])`;
- page action -> `assertOk()` plus `assertInertia(...)`;
- mutation -> redirect and toast/flash when emitted;
- controller-owned persistence -> `assertDatabaseHas()`, `assertDatabaseMissing()`, `assertSoftDeleted()`, or `assertModelMissing()`.

JSON API:

- protected unauthenticated request -> `assertUnauthorized()`;
- public endpoint -> no auth-required test unless route middleware requires it;
- validation failure -> `assertUnprocessable()->assertJsonValidationErrors([...])`;
- success -> exact JSON contract plus side effects such as token creation, identity linking, access-code usage, notification dispatch, or public-ID serialization.

## Coverage Expectations

Every routed controller action should have a matching action block unless the route intentionally excludes it. For nested controllers, compare against the deepest sibling route shape before declaring coverage complete. When a child stores redundant `Workspace` or ancestor ownership, member actions must assert same-parent mismatched ownership returns `404`, and index actions must assert those rows are excluded.

Scoped uniqueness belongs near the mutation action: same-scope duplicate fails, duplicate outside the scope succeeds when allowed, current value is allowed on update, soft-deleted reuse succeeds when the rule excludes trashed rows, and inactive-but-reserved rows remain blocked when the rule still counts them.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not keep or recreate per-module test lists in this skill.
- Do not remove controller entry-point tests because action integration tests cover internal behavior.
- Do not use real module, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/pattern-catalog.md`
- `references/tests/Feature/Http/Controllers/route-patterns.md`
- `references/tests/Feature/Http/Controllers/actions/*.md`
- `references/tests/Feature/Http/Controllers/modes/api-json.md`
- `references/tests/Feature/Http/Controllers/validation/*.md`
