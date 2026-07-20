# app/Http/Controllers

## Purpose

This reference defines project conventions for web/Inertia and API controller entrypoints under `app/Http/Controllers`.

## When To Use

Use this reference when creating or changing a controller, controller route contract, controller-owned redirect/toast, controller action delegation, or controller feature test boundary.

## Required Pattern

Use `app/Http/Controllers` for HTTP entrypoints. Keep controllers responsible for HTTP concerns: middleware, route-model binding, request validation, action input construction, action invocation, response shape, redirect, toast, and validation-error mapping.

### Web Controller Shape

- Web controllers usually implement `HasMiddleware`.
- Authorize with `new Middleware('can:...', only: [...])` when sibling controllers do.
- Return `Inertia::render(...)` for pages and redirects for mutations.
- Inertia modal support is frontend-layout driven unless sibling route/controller code already uses `Inertia::modal(...)`.
- Use model resources with `->toResource()` and collections with `->toResourceCollection()`.
- Query list pages from the owning relationship, usually ordered with `latest('id')->paginate()`.
- Use owning relationships for creates and lists that establish `Workspace` ownership.
- For member actions on already-bound child models, trust scoped binding and policies, then mutate the bound model or delegate to an action.
- Redirect with named routes and attach toasts through the shared redirect macro.
- Preserve route-model binding, scoped binding, and policy-masked not-found behavior from sibling controllers. Wrong parent/child chains should fail as binding `404` or policy `denyAsNotFound()` according to the live route shape.
- Do not query a child independently when route binding already scopes it. Use the bound model after binding, policy, and validation have passed.
- For children with redundant `Workspace` columns, preserve the extra guard used by siblings: list from the owning relationship and add the `Workspace` filter where the child stores the `Workspace` independently. For member actions on already-bound models, the equivalent guard may live in the policy as a not-found denial.
- Keep option lists shaped as `label`/`value` collections using public IDs for form values when the UI contract exposes public IDs.
- Do not silently hide deactivated records from web lists unless sibling code does. Deactivation is a nullable lifecycle timestamp distinct from soft deletion; soft-deleted records stay excluded by binding and `withoutTrashed()` rules.

Public-ID form values that persist to internal IDs are resolved at the controller boundary after validation:

```php
$attributes = $request->validated();

/** @var string $relatedRecordPublicId */
$relatedRecordPublicId = $attributes['related_record_id'];

$attributes['related_record_id'] = $workspace->relatedRecords()
    ->active()
    ->where('public_id', $relatedRecordPublicId)
    ->firstOrFail()
    ->id;
```

Option list helpers should return public IDs when the form posts public IDs:

```php
/**
 * @return Collection<int, array{label: string, value: string}>
 */
private function relatedRecordOptions(Workspace $workspace): Collection
{
    return $workspace->relatedRecords()
        ->active()
        ->orderBy('name')
        ->get()
        ->map(fn (RelatedRecord $relatedRecord): array => [
            'label' => $relatedRecord->name,
            'value' => $relatedRecord->public_id,
        ]);
}
```

### Delegated Actions

When a mutation delegates to a Data input-backed action, construct the action input at the controller boundary from validated input, then pass the typed input to the injected action.

```php
$parentRecord = $updateParentRecord->handle(
    $parentRecord,
    UpdateParentRecordInput::from($request->validated()),
);
```

For delegated actions, pass only the models, typed inputs, and scalar values the operation needs as business inputs. This applies equally to top-level and nested resources: `scopeBindings()` plus policy middleware own route hierarchy and ownership. Pass a parent only when the operation independently needs it, such as creation under that parent.

When a delegated action can still throw a domain exception after authorization/validation because it owns a transactional guard, the controller may map that exception to a validation error:

```php
try {
    $updateParentRecord->handle(
        $parentRecord,
        UpdateParentRecordInput::from($request->validated()),
    );
} catch (CannotUpdateParentRecord) {
    throw ValidationException::withMessages([
        'mode' => __('validation.prohibited', ['attribute' => 'mode']),
    ]);
}
```

Keep web/resource request and domain validation in Form Requests. Do not add `ValidationException::withMessages()` in a web controller unless a sibling controller owns the same kind of action-exception mapping. Controller feature tests for these paths prove only HTTP mapping for a mocked action exception; action integration tests prove the transactional guard itself.

### Lifecycle Controllers

For custom verbs such as activate/deactivate, enable/disable, approve/reject, confirm/unconfirm, regenerate, login/logout, subscribe/unsubscribe, print, search, export, or resend, load `lifecycle-resources.md` and model the operation as a resourceful controller before adding custom controller methods.

Lifecycle controllers should still use the HTTP boundary shape above: middleware, route models, optional request marker classes, action delegation when needed, redirect/back, and toast.

### API Controllers

- Match the live route declaration instead of assuming a generic API controller shape.
- Invokable API session endpoints can validate external credentials, create or update actors, issue Sanctum tokens, and return `access_token` plus a resource.
- External-token controllers may use SDK verification, cached key retrieval, HTTP retries, config checks, JWT decoding, nonce checks, account-conflict validation, and resource JSON responses.
- Access-code login endpoints validate a submitted code, mark it used, create or verify the actor as needed, issue a token, and return a resource JSON response.
- Current-actor endpoints can receive the authenticated actor through Laravel's current-actor injection attribute and return a resource.
- Current API session controllers may throw validation exceptions for external-token or session-domain failures.

### Tests

- Web controller tests live under `tests/Feature/Http/Controllers`.
- API controller tests live under `tests/Feature/Http/Controllers/Api`.
- Load the path-specific controller test references before writing or changing controller tests.
- When a mutation controller delegates persistence or domain side effects to a Data input-backed action, feature tests should mock that action and assert the controller passes the expected bound model/current actor plus a typed input.
- Assert only selected input properties that prove the controller boundary: input class, route-bound model identity, current actor when relevant, submitted values for the field under test, and `Optional` only when omission is the test's purpose.
- Leave persistence, defaults, nullable clearing, and action-owned side effects to action integration tests.
- If the controller catches a domain exception from a delegated action and maps it to a validation error, add a controller feature test that mocks the action to throw that exception and asserts the validation error. Keep the ordinary policy/form-request lifecycle guard test separate when that guard should stop before the action is called.
- When the mocked action returns a model for a redirect, return a persisted factory model. Do not set generated route keys such as `public_id`, `slug`, or generated codes unless the literal value is asserted.
- Do not populate unrelated attributes such as labels, codes, contact values, or phone numbers unless the controller assertion uses them.
- Do not put Pest `expect()` chains inside Mockery `withArgs(...)` or `andReturnUsing(...)` argument matching. Use boolean conditions in mock matchers and keep rich `expect()` assertions outside mocks.

Example action mock for a top-level update:

```php
$this->mock(UpdateParentRecord::class, function (MockInterface $mock) use ($parentRecord): void {
    $mock->shouldReceive('handle')
        ->once()
        ->withArgs(
            fn (ParentRecord $model, UpdateParentRecordInput $input): bool =>
                $model->is($parentRecord)
                && $input->name === 'Updated name'
        )
        ->andReturn($parentRecord);
});
```

Example action mock for a child update:

```php
$this->mock(UpdateChildRecord::class, function (MockInterface $mock) use ($childRecord): void {
    $mock->shouldReceive('handle')
        ->once()
        ->withArgs(fn (
            ParentRecord $parentRecord,
            ChildRecord $model,
            UpdateChildRecordInput $input
        ): bool => $parentRecord->is($childRecord->parentRecord)
            && $model->is($childRecord)
            && $input->name === 'Updated name')
        ->andReturn($childRecord);
});
```

Example delegated domain rejection test:

```php
it('rejects updating a child record when its parent is inactive', function (): void {
    $childRecord = ChildRecord::factory()->createOne();

    login(workspace: $childRecord->parentRecord->workspace);

    mock(UpdateChildRecord::class)
        ->shouldReceive('handle')
        ->once()
        ->withArgs(fn (
            ParentRecord $parentRecord,
            ChildRecord $model,
            UpdateChildRecordInput $input
        ): bool => $parentRecord->is($childRecord->parentRecord)
            && $model->is($childRecord)
            && $input->name === 'Updated name')
        ->andThrow(CannotUpdateChildRecord::becauseParentIsInactive());

    $response = patch(route('workspaces.parent-records.children.update', [
        'workspace' => $childRecord->parentRecord->workspace,
        'parent_record' => $childRecord->parentRecord,
        'child_record' => $childRecord,
    ]), [
        'name' => 'Updated name',
    ]);

    $response->assertRedirectBackWithErrors([
        'parent_record' => 'The parent record is inactive.',
    ]);
});
```

Compare nested web controllers against existing nested tests before finalizing. Parent mismatch, parent soft-deleted, leaf direct-parent mismatch, leaf `Workspace` mismatch, and leaf soft-deleted cases are separate cases when the resource shape supports them.

## Coverage Expectations

Read the live controller and request files in the touched path, compare them with sibling controllers and requests, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not replace controller feature tests with action integration tests when the HTTP route contract is what changed.

## Related References

- `lifecycle-resources.md`
- `references/app/Actions/README.md`
- `references/app/Http/Requests/README.md`
- `references/tests/Feature/Http/Controllers/README.md`
