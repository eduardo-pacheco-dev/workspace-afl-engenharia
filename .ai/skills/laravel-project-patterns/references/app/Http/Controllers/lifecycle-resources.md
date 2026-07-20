# Lifecycle Resource Controllers

## Purpose

Define controller naming and routing conventions for lifecycle operations that are often first described as verbs: activate/deactivate, enable/disable, approve/reject, confirm/unconfirm, login/logout, subscribe/unsubscribe, regenerate, resend, print, search, export, archive/restore, suspend/unsuspend, publish/unpublish, and similar actions.

The goal is to keep controllers CRUDdy: pick the resource being created, shown, updated, or destroyed, then use standard methods (`index`, `show`, `create`, `store`, `edit`, `update`, `destroy`) instead of adding custom action methods to an existing resource controller.

## When To Use

Use this reference when a controller or route proposal includes a non-resourceful method name such as:

- `activate`, `deactivate`, `enable`, `disable`, `toggle`
- `approve`, `reject`, `confirm`, `unconfirm`, `verify`, `unverify`
- `login`, `logout`, `authenticate`, `register`, `subscribe`, `unsubscribe`
- `regenerate`, `resend`, `print`, `search`, `export`, `archive`, `restore`
- `markAs...`, `set...`, `make...`, `send...`, `generate...`

Also use it when adding a nullable lifecycle timestamp such as `deactivated_at`, a state-management route, a controller that mutates a single attribute, or a UI action that changes a model's lifecycle state.

## Required Pattern

### Core Rule

If the operation sounds like a verb, first ask: "What resource appears, disappears, changes, or is being viewed?"

Then create or reuse a focused controller for that resource and use standard methods:

| Operation wording               | Resource/controller                    | Method    |
| ------------------------------- | -------------------------------------- | --------- |
| log in                          | `AuthenticatedSessionController`       | `store`   |
| log out                         | `AuthenticatedSessionController`       | `destroy` |
| enable a capability             | `ParentRecordCapabilityController`     | `store`   |
| disable a capability            | `ParentRecordCapabilityController`     | `destroy` |
| confirm a pending state         | `ConfirmedParentRecordStateController` | `store`   |
| regenerate codes                | `RecoveryCodeController`               | `store`   |
| list generated codes            | `RecoveryCodeController`               | `index`   |
| create a membership             | `ParentRecordMembershipController`     | `store`   |
| delete a membership             | `ParentRecordMembershipController`     | `destroy` |
| show a printable representation | `PrintableParentRecordController`      | `show`    |
| search records                  | `SearchableParentRecordController`     | `index`   |
| create a generated identifier   | `UniqueParentRecordCodeController`     | `store`   |

### Naming Decision Tree

1. **Existing canonical resource:** If the action is ordinary create/read/update/delete of the model, keep it in the model's controller.
   - `ParentRecordController@store`
   - `ParentRecordController@update`
   - `ParentRecordController@destroy`

2. **Nested child resource:** If the action creates or removes a relationship-like thing, name that thing.
   - `ParentRecordMembershipController@store` / `destroy`
   - `ActorPasskeyController@store` / `destroy`
   - `WorkspaceInvitationController@store` / `destroy`

3. **Singleton capability or lifecycle state:** If the parent can have only one such capability/state, use a singular nested resource and pair `store` with `destroy`.
   - `ParentRecordActivationController@store` activates a record.
   - `ParentRecordActivationController@destroy` deactivates a record.
   - `ParentRecordCapabilityController@store` enables a capability.
   - `ParentRecordCapabilityController@destroy` disables a capability.

4. **Negative-state timestamp:** If the persisted domain concept is explicitly the negative state, such as `deactivated_at`, prefer naming the resource after that state.
   - `ParentRecordDeactivationController@store` sets `deactivated_at`.
   - `ParentRecordDeactivationController@destroy` clears `deactivated_at`.
   - This reads better than `ParentRecordActivationController@destroy` when the column, policy, and UI are all framed as deactivation.

5. **Positive capability wording:** If the UI/business language is "activation exists" rather than "deactivation exists", use the positive capability.
   - `ParentRecordActivationController@store` creates activation.
   - `ParentRecordActivationController@destroy` removes activation.

6. **Adjective/result-state resource:** Use adjective names when the resource is naturally a result view or authenticated/confirmed state.
   - `AuthenticatedSessionController`, not `SessionAuthenticationController`, for login/logout.
   - `ConfirmedParentRecordStateController` for a confirmation event.
   - `ConfirmedStatusController` for a status read.
   - `PrintableParentRecordController` for a printable representation.

7. **Status enum or multi-state workflow:** If the operation changes among multiple states, do not invent one controller per transition unless each transition has its own permissions, validation, side effects, or UI. Use a status resource and `update`.
   - `ParentRecordStatusController@update` for multi-state updates.
   - Use separate lifecycle controllers for high-risk transitions with different authorization or side effects.

8. **Command with complex domain logic:** Keep the controller resourceful, but push the logic into an action.
   - `ParentRecordDeactivationController@store` calls `DeactivateParentRecord`.
   - `UniqueParentRecordCodeController@store` calls `GenerateParentRecordCode`.
   - A service/action name can be verb-first; a controller name should remain resource-first.
   - For lifecycle actions, pass only the target model and independent business inputs. Scoped bindings and policies already own route hierarchy and ownership.

9. **Pure read representation:** If no mutation happens, pick `index` or `show`.
   - Search collection: `SearchableParentRecordController@index`.
   - Printable single resource: `PrintableParentRecordController@show`.
   - Export collection: `ParentRecordExportController@store` when it creates an export job/file; `ParentRecordExportController@show` when it only streams an existing export.

10. **One-off side effects:** Avoid `__invoke` as a shortcut for vague verbs. Use invokable controllers for route endpoints that are already first-class single-action resources in nearby code, or when framework precedent uses invokable view/verification controllers.

### Route Patterns

Choose the route registration helper by the cardinality of the resource:

- Prefer `Route::singleton()` for virtual or real child resources where only one instance can exist for the parent and the controller exposes multiple resourceful routes, especially paired `store` / `destroy` lifecycle states.
- Use `Route::resource()` for child resources with their own identity and potentially many rows.
- Use explicit routes when the controller has a single command-style endpoint, when singleton routing would obscure a local convention, when it requires a route shape Laravel's singleton helper cannot express clearly, or when it would make the surrounding route file harder to read.

Prefer singular resource URIs for singleton lifecycle resources:

```php
Route::singleton('parent-records.activation', ParentRecordActivationController::class)
    ->creatable()
    ->only(['store', 'destroy']);
```

For a `deactivated_at` lifecycle timestamp:

```php
Route::singleton('workspaces.parent-records.deactivation', ParentRecordDeactivationController::class)
    ->creatable()
    ->only(['store', 'destroy']);
```

When the lifecycle route only creates or sets a singleton state through one endpoint, prefer an explicit route instead of `Route::singleton(...)->creatable()->only(['store'])`:

```php
Route::post('workspaces/{workspace}/parent-records/{parent_record}/initial-status', [InitialParentRecordStatusController::class, 'store'])
    ->name('workspaces.parent-records.initial-status.store');
```

Singleton routing preserves the desired names and avoids a fake child parameter:

```txt
POST   /parent-records/{parent_record}/activation   parent-records.activation.store
DELETE /parent-records/{parent_record}/activation   parent-records.activation.destroy
```

Explicit routes remain valid when `Route::singleton()` does not fit the local route shape:

```php
Route::post('/parent-records/{parent_record}/activation', [ParentRecordActivationController::class, 'store'])
    ->name('parent-records.activation.store');

Route::delete('/parent-records/{parent_record}/activation', [ParentRecordActivationController::class, 'destroy'])
    ->name('parent-records.activation.destroy');
```

Do not use regular nested `resource()` for singleton lifecycle resources when it would imply a child route key:

```php
// Avoid for activation/deactivation-style singleton resources.
Route::resource('parent-records.activation', ParentRecordActivationController::class)
    ->only(['store', 'destroy']);
```

Use regular `resource()` when the child really has an identity:

```php
Route::resource('parent-records.children', ChildRecordController::class)
    ->only(['index', 'store', 'destroy']);
```

Use `PATCH` / `PUT` and `update` only when the request changes a value rather than creating/removing a lifecycle resource:

```php
Route::patch('/parent-records/{parent_record}/status', [ParentRecordStatusController::class, 'update'])
    ->name('parent-records.status.update');
```

### Controller Examples

The app globally calls `Model::unguard()` during application boot, so lifecycle controllers should use `update([...])` for ordinary model attribute mutations. Do not use `forceFill(...)->save()` as a mass-assignment workaround.

Small virtual lifecycle resource without a backing table:

```php
final class ParentRecordActivationController extends Controller
{
    public function store(ParentRecord $parentRecord): RedirectResponse
    {
        $parentRecord->update(['deactivated_at' => null]);

        return to_route('parent-records.show', $parentRecord)->toast('Record activated.');
    }

    public function destroy(ParentRecord $parentRecord): RedirectResponse
    {
        $parentRecord->update(['deactivated_at' => now()]);

        return to_route('parent-records.show', $parentRecord)->toast('Record deactivated.');
    }
}
```

Negative-state timestamp resource:

```php
final class ParentRecordDeactivationController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:update,parent_record'),
        ];
    }

    public function store(Workspace $workspace, ParentRecord $parentRecord): RedirectResponse
    {
        if ($parentRecord->deactivated_at === null) {
            $parentRecord->update(['deactivated_at' => now()]);
        }

        return back()->toast(__('parent_record.deactivated.title'));
    }

    public function destroy(Workspace $workspace, ParentRecord $parentRecord): RedirectResponse
    {
        if ($parentRecord->deactivated_at !== null) {
            $parentRecord->update(['deactivated_at' => null]);
        }

        return back()->toast(__('parent_record.reactivated.title'));
    }
}
```

Complex lifecycle transition with an action:

```php
final class ParentRecordDeactivationController implements HasMiddleware
{
    public function store(
        Workspace $workspace,
        ParentRecord $parentRecord,
        DeactivateParentRecord $deactivateParentRecord,
    ): RedirectResponse {
        try {
            $deactivateParentRecord->handle($parentRecord);
        } catch (CannotDeactivateParentRecord) {
            throw ValidationException::withMessages([
                'parent_record' => __('parent_record.validation.active_children'),
            ]);
        }

        return back()->toast(__('parent_record.deactivated.title'));
    }
}
```

### Authorization Pattern

Controllers stay resourceful; policy ability names may stay verb-oriented because permissions are actions.

Examples:

```php
public static function middleware(): array
{
    return [
        new Middleware('can:update,parent_record'),
    ];
}
```

Use existing update/delete abilities for lifecycle transitions that are ordinary update/delete permissions for the model. Use transition-specific abilities only when the workflow has separate authorization or denial messages.

```php
public static function middleware(): array
{
    return [
        new Middleware('can:deactivate,parentRecord', only: ['store']),
        new Middleware('can:reactivate,parentRecord', only: ['destroy']),
    ];
}
```

Prefer the ability names already used by sibling policies. If no local precedent exists, choose names that read clearly in tests and denial messages (`activate`, `deactivate`, `reactivate`, `approve`, `reject`).

### Data Modeling Pattern

- A lifecycle controller does not require a model/table. It may manage a virtual singleton resource backed by a timestamp, boolean, enum, JSON column, external service, or relationship row.
- Add a table only when the lifecycle resource has independent attributes, history, ownership, audit requirements, or a true has-many relationship.

## Coverage Expectations

Read the controller, route, action, request, and sibling controller files that define the lifecycle surface. Cover HTTP behavior in feature tests and action-owned transactional guards in action integration tests.

## Do Not

- Do not add custom verb methods to an existing resource controller when a resourceful lifecycle controller fits.
- Do not use `forceFill(...)->save()` for ordinary lifecycle mutations.

## Related References

- `references/app/Http/Controllers/README.md`
- `references/app/Actions/README.md`
- `references/tests/Feature/Http/Controllers/route-patterns.md`
