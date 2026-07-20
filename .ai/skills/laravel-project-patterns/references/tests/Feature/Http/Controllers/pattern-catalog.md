# Controller Test Pattern Catalog

## Purpose

This catalog lists reusable controller-test patterns for the system. Use it to decide which test families apply to a new or changed controller. Do not copy it as a per-controller checklist.

## When To Use

Use this reference when a controller test needs a cross-cutting catalog of route shapes, ordering layers, binding boundaries, validation families, transport modes, and side-effect ownership.

## Required Pattern

Workflow:

1. Choose the route shape from `route-patterns.md`.
2. Choose the action baseline from `actions/*.md`.
3. Choose the transport guide from `modes/api-json.md` for JSON endpoints.
4. Merge only the validation references that match the real Form Request from `validation/*.md`.
5. Apply catalog rows only when the controller, route binding, policy, request, resource, or side effect makes the row reachable.

### Route Shape / Action Matrix

| Route shape                    | Actions                                                                      | Reusable test families                                                                                                |
| ------------------------------ | ---------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| Settings or singleton route    | single page or mutation                                                      | auth redirect, `403`, page contract, mutation contract                                                                |
| Two-resource `Workspace` chain | resource actions under a `Workspace`                                         | auth, `403`, `Workspace`-scoped `404`, list scoping, validation, redirects/toasts                                     |
| Three-resource route chain     | resource actions under one direct parent                                     | all two-resource families plus direct-parent mismatch, parent-scoped uniqueness, parent-scoped list exclusion         |
| Four-resource route chain      | resource actions under parent and child ancestors                            | outer ancestor `404`, middle parent `404`, leaf `404`, wrong ancestor graph, redundant ownership mismatch             |
| Invokable nested action        | one `__invoke` route                                                         | auth, authorization, binding/scope, success side effect; no artificial CRUD grouping                                  |
| Public JSON endpoint           | session, external-identity, or access-code endpoints without auth middleware | validation first, external verification/domain failures, success JSON, identity/actor/token/notification side effects |
| Protected JSON endpoint        | JSON endpoint requiring auth/token                                           | guest `401`, authenticated success, public-ID JSON contract                                                           |

### Complete Per-Action Order Matrix

Apply every row that the controller contract can reach.

| Layer             | Create                                                                  | Index                                                                                            | Show                                                                      | Edit                                                    | Store                                                                                            | Update                                                                                                     | Destroy                                       |
| ----------------- | ----------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------- | ------------------------------------------------------- | ------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------- | --------------------------------------------- |
| Auth              | requires authentication                                                 | requires authentication                                                                          | requires authentication                                                   | requires authentication                                 | requires authentication                                                                          | requires authentication                                                                                    | requires authentication                       |
| Authorization     | prevents viewing from an unrelated Workspace                            | prevents listing from an unrelated Workspace                                                     | prevents viewing from an unrelated Workspace                              | prevents viewing from an unrelated Workspace            | prevents creating from an unrelated Workspace                                                    | prevents updating from an unrelated Workspace                                                              | prevents deleting from an unrelated Workspace |
| Ancestor binding  | ancestor wrong Workspace or soft deleted                                | same ancestor order                                                                              | same ancestor order                                                       | same ancestor order                                     | same ancestor order                                                                              | same ancestor order                                                                                        | same ancestor order                           |
| Parent graph      | parent wrong ancestor, wrong Workspace, or soft deleted                 | same parent order                                                                                | same parent order                                                         | same parent order                                       | same parent order                                                                                | same parent order                                                                                          | same parent order                             |
| Leaf graph        | not applicable                                                          | not applicable                                                                                   | leaf wrong parent, wrong ancestor graph, wrong Workspace, or soft deleted | same as show                                            | not applicable before create                                                                     | same as show                                                                                               | same as show                                  |
| Lifecycle state   | create page blocked or allowed by contract                              | list continuity or exclusion by contract                                                         | read continuity or hidden by contract                                     | edit blocked or allowed by contract                     | mutation blocked before validation or action                                                     | mutation blocked before validation or action                                                               | deletion blocked before success               |
| Validation/domain | query validation only when present                                      | filter validation only when present                                                              | none                                                                      | none                                                    | field dataset, scoped exists/unique, request-owned cross-field rules, mapped action exceptions   | field dataset, stored-value comparisons, scoped unique ignore, nullable clearing, mapped action exceptions | delete guards or mapped action exceptions     |
| Primary success   | shows create page                                                       | lists records                                                                                    | shows record                                                              | shows edit page                                         | creates or delegates create                                                                      | updates or delegates update                                                                                | deletes or delegates delete                   |
| Extra variants    | reference-data props, selected/null relationship props, partial reloads | other Workspace exclusion, other-parent exclusion, wrong graph exclusion, soft-deleted exclusion | related/null state, read-continuity variants                              | derived booleans, reference-data props, partial reloads | public-ID resolution, scoped uniqueness successes, adjacent/open-ended ranges when request-owned | partial update, null clearing, current value allowed, open-ended changes                                   | cleanup/default side effects                  |

### Page Response / Inertia Backend Contract Patterns

- Assert the component string.
- Assert every ancestor public ID prop used by the page.
- Assert the shown or edited resource public ID.
- Assert enum, reference-data, and option props when the form needs them.
- Assert derived booleans that lock or alter form fields.
- Assert partial reload props with `reloadOnly(...)` when the controller supports dependent options.
- Assert index collections include in-scope records and omit out-of-scope records.
- Keep test names aligned to the order layer: `prevents`, `returns not found`, `validates`, `rejects`, `allows`, `clears`, `preserves`, `does not include`, or the primary success phrase.

### Access / Order Patterns

- Web unauthenticated actions redirect to the login route.
- Protected JSON endpoints return `401` for guests.
- Authorization denials are `403` when route bindings resolve.
- Binding failures and scoped chain mismatches are `404`.
- Policy-masked ownership failures that intentionally hide existence are also `404`, named as policy-masked cases.
- Binding checks go from outer ancestor to direct parent to leaf.
- Soft-deleted models are tested beside the boundary they belong to.
- Lifecycle state checks come after binding and before validation or success.
- Validation datasets come after access/binding and before success.
- Invokable controllers stay in their own focused files.

### Nested Binding Patterns

- `Workspace`-scoped model belongs to the route `Workspace`.
- Direct child belongs to the route parent.
- Deep leaf belongs to the middle parent, the middle parent belongs to the outer parent, and the full chain belongs to the route `Workspace`.
- Same-`Workspace` wrong-parent graphs must be `404` or excluded.
- Cross-`Workspace` children must be `404` even when their public IDs are valid.
- Redundant ownership mismatch must be `404` or excluded when a child stores both direct-parent and `Workspace`/ancestor ownership.
- Trashed route models should be `404` unless the route explicitly includes them.

### Index / List Patterns

Index tests prove collection scoping:

- include an in-scope row;
- exclude another Workspace;
- exclude another direct parent;
- exclude same-`Workspace` wrong ancestor graphs;
- exclude redundant ownership mismatches;
- exclude soft-deleted rows unless included by contract;
- preserve serialized public-ID resource shape.

### Store / Update Validation Patterns

Use focused datasets and named tests near `store` and `update` actions.

- Dataset base validation covers required fields, types, arrays, nested fields, formats, and invalid values.
- Update tests prove omitted fields are allowed only when the update contract allows them.
- Enum, string length, numeric, integer, decimal precision, min/max, and same-payload comparisons are dataset cases.
- Stored-value comparisons on update are named tests.
- Paired fields with `required_with` cover both directions.
- Public IDs are validated in the current `Workspace`/parent scope and resolved to internal IDs only for persistence.
- Nullable relationships get success tests for assignment and clearing when the controller owns the behavior.
- Request-owned dependent-record prohibitions stay in Form Request validation tests.
- Action-owned dependent-record guards are mocked in controller tests only for exception-to-validation mapping and are fully proven in action integration tests.
- Scoped uniqueness covers same-scope failure, current-record ignore on update, allowed cross-scope reuse, soft-deleted reuse when permitted, and inactive reserved rows when still counted.
- Normalized public fields should be tested using the submitted public field.
- Range/overlap rules stay in controller validation only when the Form Request owns them. If the rule needs locks, transactional reads, or dependent rows that can change concurrently, move the guard to the action and keep the controller test focused on mapped validation.

### Success / Side-Effect Patterns

- Data input-backed action delegation changes the owner: controller tests own HTTP boundary, action invocation, redirect/toast/JSON response, and minimal input mapping; action integration tests own persistence, defaults, nullable clearing, generated values, locks, transactions, domain exceptions, and side effects.
- Actions receive only the models and inputs required by the operation. A create action may receive the direct business parent.
- Mocked action argument callbacks return booleans.
- Mocked create/store actions return persisted models only when the controller needs a route key.
- Controller-owned persistence uses database assertions for durable effects.
- `$model->refresh()` with `expect()` is reserved for reloaded Eloquent behavior.
- Public IDs submitted by forms persist as the resolved internal FK when the controller owns resolution.
- Default switching assertions belong to the suite that owns the side effect.
- Delete cleanup, soft delete, hard delete, or detach/reset assertions belong to the suite that owns the mutation.
- Web mutations assert named-route redirects and one-argument toast/flash output when emitted.
- JSON session success asserts token creation, identity linking, access-code usage, actor creation/reuse, and notification dispatch when those are part of the endpoint contract.

### Focused Variant Examples

Invokable nested default mutations keep the same entry-point coverage even when they do not fit a CRUD `describe(...)` block:

```php
it('sets a child record as default', function (): void {
    $previousDefault = ChildRecord::factory()->default()->createOne();
    $childRecord = ChildRecord::factory()
        ->for($previousDefault->parentRecord)
        ->createOne(['is_default' => false]);

    login(workspace: $childRecord->parentRecord->workspace);

    $response = patch(route('workspaces.parent-records.children.make-default', [
        'workspace' => $childRecord->parentRecord->workspace,
        'parent_record' => $childRecord->parentRecord,
        'child_record' => $childRecord,
    ]));

    $response->assertRedirectToRoute('workspaces.parent-records.children.index', [
        'workspace' => $childRecord->parentRecord->workspace,
        'parent_record' => $childRecord->parentRecord,
    ])
        ->assertToast('Default child record updated');

    expect($previousDefault->refresh()->is_default)->toBeFalse()
        ->and($childRecord->refresh()->is_default)->toBeTrue();
});
```

Controller-owned destroy cleanup must assert every durable side effect owned by the controller, not only the deleted leaf:

```php
it('deletes a parent record and its operational records', function (): void {
    $parentRecord = ParentRecord::factory()->createOne();
    $defaultChildRecord = ChildRecord::factory()
        ->for($parentRecord)
        ->default()
        ->createOne();
    $operationalRecord = OperationalRecord::factory()
        ->for($parentRecord)
        ->createOne();

    login(workspace: $parentRecord->workspace);

    $response = delete(route('workspaces.parent-records.destroy', [
        'workspace' => $parentRecord->workspace,
        'parent_record' => $parentRecord,
    ]));

    $response->assertRedirectToRoute('workspaces.parent-records.index', [
        'workspace' => $parentRecord->workspace,
    ])
        ->assertToast('Parent record deleted');

    assertSoftDeleted($defaultChildRecord);
    assertSoftDeleted($operationalRecord);
    assertSoftDeleted($parentRecord);

    expect($parentRecord->defaultChildRecord()->exists())->toBeFalse();
});
```

Lifecycle controllers that delegate to actions keep dependency failures at the HTTP boundary and leave the real guard to `tests/Integration/Actions`:

```php
it('prevents deactivating a parent record with dependent records', function (): void {
    $parentRecord = ParentRecord::factory()->createOne();

    login(workspace: $parentRecord->workspace);

    mock(DeactivateParentRecord::class)
        ->shouldReceive('handle')
        ->once()
        ->withArgs(fn (ParentRecord $parentRecordArgument): bool => $parentRecordArgument->is($parentRecord))
        ->andThrow(CannotDeactivateParentRecord::becauseDependentRecordsExist());

    $response = post(route('workspaces.parent-records.deactivation.store', [
        'workspace' => $parentRecord->workspace,
        'parent_record' => $parentRecord,
    ]));

    $response->assertRedirectBackWithErrors([
        'parent_record' => 'This record cannot be deactivated while dependent records exist.',
    ]);
});
```

Dependent option pages assert both the full page contract and the focused partial reload:

```php
it('loads related options for the selected parent option', function (): void {
    $workspace = Workspace::factory()->createOne();
    $relatedOption = RelatedOption::factory()->createOne();

    login(workspace: $workspace);

    $response = get(route('workspaces.parent-records.create', [
        'workspace' => $workspace,
        'parent_option' => $relatedOption->parent_option,
    ]));

    $response->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($relatedOption): void {
            $page->component('parent-records/Create')
                ->where('parentOption', $relatedOption->parent_option)
                ->reloadOnly('relatedOptions', function (AssertableInertia $reload) use ($relatedOption): void {
                    $reload->where('relatedOptions.0.value', $relatedOption->public_id);
                });
        });
});
```

When edit or show pages allow the stored related record to remain visible after it becomes unavailable for new records, assert that exception explicitly. Cover the unavailable states that the controller intentionally preserves, such as inactive, deactivated, or soft-deleted related records.

```php
it('shows the edit page with the stored unavailable related record', function (): void {
    $parentRecord = ParentRecord::factory()->createOne();
    $relatedRecord = RelatedRecord::factory()->unavailable()->createOne();

    $parentRecord->update(['related_record_id' => $relatedRecord->id]);

    login(workspace: $parentRecord->workspace);

    $response = get(route('workspaces.parent-records.edit', [
        'workspace' => $parentRecord->workspace,
        'parent_record' => $parentRecord,
    ]));

    $response->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($relatedRecord): void {
            $page->component('parent-records/Edit')
                ->where('relatedOptions.0.value', $relatedRecord->public_id);
        });
});
```

```php
it('shows the record with the stored soft-deleted related record', function (): void {
    $parentRecord = ParentRecord::factory()->createOne();
    $relatedRecord = RelatedRecord::factory()
        ->trashed()
        ->createOne();

    $parentRecord->update(['related_record_id' => $relatedRecord->id]);

    login(workspace: $parentRecord->workspace);

    $response = get(route('workspaces.parent-records.show', [
        'workspace' => $parentRecord->workspace,
        'parent_record' => $parentRecord,
    ]));

    $response->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($relatedRecord): void {
            $page->component('parent-records/Show')
                ->where('relatedRecord.deleted_at', $relatedRecord->deleted_at->toJSON())
                ->where('relatedRecord.id', $relatedRecord->public_id);
        });
});
```

### Transport / API Patterns

- Web/session uses route helpers, authenticated sessions, redirects, validation errors, Inertia contracts, persistence assertions, and toast/flash assertions.
- Public JSON session endpoints start with validation because no auth middleware should block them.
- External verification failures are domain cases after validation passes.
- Existing external identities should authenticate the existing actor and return the expected token/JSON contract.
- Changed external email should not create a new actor when the identity match controls the contract.
- Email conflicts and external-id conflicts need separate branches.
- Missing external claims need a dedicated failure branch when the controller handles it.
- Access-code request endpoints assert action invocation and notification dispatch.
- Access-code login endpoints reject expired and used codes, then prove new-actor and existing-actor success branches.
- Protected actor endpoints assert guest `401`, then authenticated public-ID JSON.

### Applicability Rule

Require a behavior only after the live route, controller, request, action, resource, policy, or tests make it part of the current contract. Keep reusable examples in this catalog, but do not treat every example as mandatory for every controller.

## Coverage Expectations

Use this catalog only after loading the route shape, action template, transport mode, and validation references that match the touched controller. Coverage claims must map to live route, controller, request, policy, action, resource, or test evidence.

## Do Not

- Do not keep per-module test lists in this skill.
- Do not copy one module's domain example as a universal requirement.
- Do not create fake application PHP tests from this catalog.
- Do not drop controller entry-point coverage because action integration tests cover internal state.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
- `references/tests/Feature/Http/Controllers/route-patterns.md`
- `references/tests/Feature/Http/Controllers/actions/*.md`
- `references/tests/Feature/Http/Controllers/modes/api-json.md`
- `references/tests/Feature/Http/Controllers/validation/*.md`
