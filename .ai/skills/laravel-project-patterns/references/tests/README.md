# Tests Path Map

## Purpose

This reference defines conventions for the root `tests/` path map.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Mirror the repository's `tests/` paths when choosing or creating a reference. Do not collapse these into one broad testing note when a path-specific reference exists.

### Current Test Paths

- `tests/ArchitectureTest.php` -> `references/tests/ArchitectureTest.md`
- `tests/.pest/shards.json` -> `references/tests/.pest/shards.md`
- `tests/Pest.php` -> `references/tests/Pest.md`
- `tests/TestCase.php` -> `references/tests/TestCase.md`
- `tests/Unit/Enums/*Test.php` -> `references/tests/Unit/Enums/README.md`
- `tests/Unit/Models/*Test.php` -> `references/tests/Unit/Models/README.md`
- `tests/Integration/Actions/*Test.php` -> `references/tests/Integration/Actions/README.md`
- `tests/Integration/Http/Resources/*ResourceTest.php` -> `references/tests/Integration/Http/Resources/README.md`
- `tests/Integration/Listeners/*Test.php` -> `references/tests/Integration/Listeners/README.md`
- `tests/Integration/Models/*Test.php` -> `references/tests/Integration/Models/README.md`
- `tests/Integration/Models/Concerns/*Test.php` -> `references/tests/Integration/Models/Concerns/README.md`
- `tests/Integration/Support/Media/*Test.php` -> `references/tests/Integration/Support/Media/README.md`
- `tests/Feature/Console/*Test.php` -> `references/tests/Feature/Console/README.md`
- `tests/Feature/Http/Controllers/*ControllerTest.php` -> `references/tests/Feature/Http/Controllers/README.md`
- `tests/Feature/Http/Controllers/Api/*ControllerTest.php` -> `references/tests/Feature/Http/Controllers/Api/README.md`
- `tests/Feature/Http/Middleware/*Test.php` -> `references/tests/Feature/Http/Middleware/README.md`
- `tests/Feature/Models/Concerns/*Test.php` -> `references/tests/Feature/Models/Concerns/README.md`
- `tests/Support/Models/*` -> `references/tests/Support/Models/README.md`
- `tests/TestSupport/*` -> `references/tests/TestSupport/README.md`
- `tests/migrations/*` -> `references/tests/migrations/README.md`
- `tests/testfiles/*` -> `references/tests/testfiles/README.md`

### Suite Routing

- Before adding or deleting a test, map it to the behavior owner: route contract, request rule, policy branch, resource shape, persistence invariant, changed behavior, or regression risk. Do not add tests only to mirror a template or sibling count.
- Use `Unit` when the behavior can be proven without HTTP and usually without persisted relationship graphs: enum values, model casts, model defaults, traits present on a class, simple accessors, and pure model methods.
- Use `Integration` when the behavior depends on persistence, factories, observers, resources, relationships, media library events, support adapters, external collaborator fakes, or database-enforced business invariants.
- Use `Feature` when the behavior enters through routes, HTTP verbs, Inertia pages, API JSON, middleware, route model binding, console commands, auth/session behavior, or redirects.
- Use `Browser` only when a real browser is needed for browser-side behavior. There is no current `tests/Browser` tree, so do not create one casually.
- Use architecture tests for broad static rules that should hold across namespaces, not for feature behavior.
- For nested web controller work, treat the full route chain as behavior. Test every ancestor and leaf binding boundary that the route can reject, including soft-deleted ancestors/leaves when the models use `SoftDeletes`.
- Exact JSON resource contracts belong in `tests/Integration/Http/Resources`, even when controller tests assert selected Inertia paths that depend on the same resource output.
- Database check constraints and partial unique indexes that enforce system rules belong in `tests/Integration/Models` when they can be proven by direct persistence. Examples include coordinate range checks and active-name/default-row uniqueness per parent.
- Layered coverage is not duplication when each suite proves a different owner. Keep both an integration model test for direct database enforcement and a controller feature test for HTTP validation, redirects, and messages when the same invariant exists at both layers. Remove or avoid only tests that re-prove the same owner twice, such as a controller test that only asserts a database constraint without an HTTP contract or a model integration test that only mirrors request validation.

### Persistence Assertions

Use database assertions when the contract is durable storage:

- `assertDatabaseHas()` for created or updated rows and normalized/resolved values.
- `assertDatabaseMissing()` for rows that must not exist.
- `assertSoftDeleted()` for soft-deleting models.
- `assertModelMissing()` for hard deletes.

Use `$model->refresh()` with `expect()` when the contract is the Eloquent model after reload: casts, accessors, relationships, timestamps, dirty/original state, or other model-level behavior. Do not assert the same ordinary persisted field both ways unless the raw database value and the reloaded Eloquent value are separate contracts.

### Update Rule

When changing one application surface, update every affected test path:

- model cast/default/trait -> `tests/Unit/Models`
- persisted relationships, observers, route key persistence -> `tests/Integration/Models`
- resource contract -> `tests/Integration/Http/Resources`
- web route behavior -> `tests/Feature/Http/Controllers`
- API route behavior -> `tests/Feature/Http/Controllers/Api`
- middleware sharing/gating -> `tests/Feature/Http/Middleware`
- command behavior -> `tests/Feature/Console`
- support helper behavior -> the matching integration path for the touched support area

The canonical boundary for `tests/Integration/Models/**` is in `references/tests/Integration/Models/README.md`; use this file to choose the suite, then load the path reference before writing model tests.

Controller feature tests are not optional when a route, form request, policy, resource payload, redirect, toast, or binding chain changes. Resource integration tests are not a substitute for controller tests, and controller tests are not a substitute for exact resource contract tests.

When a controller list action scopes records, include exclusion coverage for records outside the route `Workspace` and for records under a different direct parent in the same `Workspace` when the route is nested. Use exact list counts plus `whereNot(...)` on the excluded public id.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
- `references/tests/Feature/Http/Controllers/Api/README.md`
- `references/tests/Feature/Console/README.md`
- `references/tests/Feature/Http/Middleware/README.md`
- `references/tests/Integration/Actions/README.md`
- `references/tests/Integration/Http/Resources/README.md`
- `references/tests/Integration/Models/README.md`
- `references/tests/Unit/Enums/README.md`
- `references/tests/Unit/Models/README.md`
