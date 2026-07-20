---
name: laravel-project-patterns
description: 'Project conventions for Laravel projects, including application code, project-supporting PHP, Inertia Blade shells, React Email templates, schema-backed models, and Pest test routing. Use when creating or changing routes, config, localization, bootstrap/public entrypoints, seeders, root tooling PHP, models, migrations, relationships, casts, factories, resources, actions, console commands, middleware, listeners, policies, providers, support classes, resources/views Blade shells, resources/react-email mail templates/exports, or tests under Unit, Integration, Feature, or Architecture, plus Browser only when a real browser suite exists. Encodes strict typed PHP, unguarded Eloquent models, public NanoID route keys, indexed *_id columns without database foreign key constraints, migrations without down() methods, controller/API feature test ordering, and system-logic model integration tests.'
---

# Laravel Project Patterns

Use this skill before editing Laravel project backend application code, project-supporting PHP, Inertia Blade shells, React Email templates, or choosing tests. The references cover Laravel application code, routes, config, localization, bootstrap/public entrypoints, seeders, root tooling PHP, `resources/views` Blade shells, `resources/react-email` mail templates, schema-backed code, migrations, Eloquent models, factories, resources, actions, console commands, middleware, listeners, policies, support classes, and Pest tests across unit, integration, feature, and architecture suites, with Browser guidance only when a real browser suite is introduced.

This skill is derived from the live repository. When repository evidence disagrees with a generic Laravel habit, the repository wins. Read the exact files in the touched path first, then load the matching reference here, then compare with the nearest sibling module before writing code or tests. For controller-test work, start at `references/tests/Feature/Http/Controllers/README.md`; it is the central map for action order, web/API transport differences, nested `Workspace` boundaries, validation datasets, redirects/toasts, and public-id contracts.

Examples in this skill and its references are canonical synthetic examples. They merge compatible patterns observed across modules and intentionally use placeholder names such as `Workspace`, `ParentRecord`, `ChildRecord`, `LeafRecord`, `RelatedRecord`, `Actor`, `ExampleInput`, and `ExampleResource`. Do not replace those examples with real module/entity names.

## First Pass

1. Read the nearest `AGENTS.md` and application guidelines.
2. Search version-specific docs before code changes when Laravel Boost is available.
3. Inspect the exact file being changed and sibling files in the same area before generating anything:
   - `database/migrations/*`
   - `routes/*.php`
   - `config/*.php`
   - `lang/**/*.php`
   - `bootstrap/*.php`
   - `public/*.php`
   - `database/seeders/*.php`
   - root tooling PHP and tracked auxiliary PHP guidance
   - `app/**`
   - `resources/views/**`
   - `resources/react-email/**`
   - `database/factories/**`
   - `tests/Unit/**`
   - `tests/Integration/**`
   - `tests/Feature/**`
   - `tests/Browser/**` if present
4. If adding or changing a controller feature test, load `references/tests/Feature/Http/Controllers/README.md` first, then the matching action, route, mode, and validation references under that directory.
5. For nested controller tests, compare against the deepest sibling controller tests before deciding a case is unnecessary. Treat Inertia assertions as backend response contracts for page component names, props, redirects, and flashes/toasts.

## Reference Skeleton

All `references/**` markdown files follow the canonical structure documented in `references/README.md`. Preserve existing pattern coverage, examples, datasets, and snippets while converting real module/entity examples to synthetic placeholders.

## Non-Negotiables

- Preserve concurrent changes. Re-read a file immediately before patching it.
- Keep new code aligned to the existing architecture. Do not add base folders, dependencies, or broad abstractions unless the task explicitly needs them.
- Use `php artisan make:* --no-interaction` for Laravel-created files when practical, then rewrite generated output to match local patterns.
- Do not add database foreign key constraints when the repository uses schema-planning tools or application-level relationships instead. Use indexed `foreignId` columns only.
- Do not add migration `down()` methods when existing migrations intentionally omit them.
- Do not add `$fillable` or `$guarded` on models when the app globally calls `Model::unguard()`.
- Because models are globally unguarded, use `$model->update([...])` for normal persisted attribute mutations in app-owned code. Do not use `forceFill(...)->save()` as a mass-assignment workaround.
- Every behavioral change needs a focused programmatic test.
- Every new test must prove changed behavior, an interface contract, a regression risk, or a changed owner surface. Do not add tests only because an action template contains a similar example.
- Name action integration tests after the observable behavior. For the primary create success case, use a direct name such as `creates a child record`; do not append the parent or owner merely because the persisted row includes its ID. Add a scope qualifier only when that scope is the behavior under test, such as cross-parent isolation or an active-parent guard.
- Assert durable database effects with `assertDatabaseHas()`, `assertDatabaseMissing()`, `assertSoftDeleted()`, or `assertModelMissing()` according to the persistence contract. Do not assert fixture setup; if a test needs a soft-deleted fixture, create it with the factory `trashed()` state instead of creating an active model and calling `delete()`. Keep the soft-deleted parent in a local variable for route/login arguments because normal `belongsTo` queries may filter it out. If a child factory derives ownership through a normal parent query that cannot see trashed rows, pass only the minimum FK/owner IDs needed to create the child under that trashed parent. Use `$model->refresh()` with `expect()` only when the test is proving reloaded Eloquent model behavior, such as casts, accessors, relationships, timestamps, or dirty/original state.
- Controller feature tests are the exception when the mutation delegates persistence to a Data input-backed action: mock the action and assert the HTTP boundary plus the request-to-input mapping needed by the scenario. For create/store primary success, a required-only payload may assert the required input mapping. For partial update, keep cases partial and assert submitted fields plus request-normalized fields only where relevant. Leave persistence, optional/default behavior, nullable clearing, and side effects to `tests/Integration/Actions`.
- Controller feature tests remain required for delegated mutations because the controller is an entry point. Do not remove controller tests as duplicate only because `tests/Integration/Actions` covers the internal guard. Keep the controller assertions for authentication, authorization, scoped binding, request validation, action invocation, request-to-input mapping, redirects/toasts, and exception-to-validation mapping.
- For delegated destroy or lifecycle actions, keep controller feature tests at the HTTP boundary: mock the action, assert the bound model is passed, and assert exception-to-validation mapping. Do not use one generic mock as a reason to delete concrete route-contract tests for distinct dependency families, such as configuration rows versus operational rows that make deletion invalid. When those cases remain in the controller suite, set up the minimum fixture that names the route scenario, mock the action to throw, and preserve historical active and soft-deleted dependency variants when they protect the route contract. Leave deletion state, transaction internals, and guard truth to `tests/Integration/Actions`.
- Actions must accept only the models, payloads, and independent values that are business inputs to the operation. Do not pass route hierarchy solely to repeat ownership checks already enforced by scoped bindings and policies at the entrypoint.
- Do not re-query a model passed to an action solely to prove ownership, existence, or soft-delete state. Query fresh state only when the action owns a transactional guard, lock, or required relationship read.
- A parent or owner belongs in the action signature only when the operation needs it as a business input, such as creating a row through that parent. Otherwise derive required business relationships from the target model instead of making callers reconstruct the route hierarchy.
- When a controller test mocks a create/store action and the controller needs the returned model for a redirect route, return a persisted factory model with `createOne()`. Do not set generated route keys such as `public_id`, `slug`, or generated codes unless the literal value is asserted. Set only required relationships and the minimum non-conflicting domain attributes needed to let validation reach the mocked action.
- Do not put Pest `expect()` chains inside Mockery argument matchers. Mock callbacks should return booleans and check only the arguments needed for that controller contract.
- Keep `tests/Integration/Models/**` limited to project/system behavior. Use `references/tests/Integration/Models/README.md` for the canonical boundary and avoid generic Laravel relationship, FK/ID equality, related-model type, or factory/count smoke tests.
- Do not create only the most obvious test file. Update related tests when a change alters a related model, resource, controller, action, middleware, console command, or support surface.
- Controller feature tests follow the action-first, failure-to-success matrix in `references/tests/Feature/Http/Controllers/README.md`. Nested routes must cover every scoped binding boundary, including redundant ownership mismatches when a child stores denormalized `Workspace` or ancestor IDs.
- Store/update validation uses public IDs when the form contract exposes public IDs. Convert to internal integer IDs after validation in the controller only when persistence requires it; do not force `prepareForValidation()` solely to make public IDs fit integer columns.
- For Form Request rules that mention server-managed or unknown fields, inspect the application service-provider form-request bootstrap first. The app calls `FormRequest::failOnUnknownFields()`, so do not add `exclude` to silently drop submitted input; leave non-contract fields out of `rules()`.
- Prefer database constraints for invariants that PostgreSQL can enforce. Add an explicit lock only for a documented cross-row invariant that cannot reasonably live in the database, and make every competing action lock the same parent row in the same order.
- Put web/resource request validation, normalization, and domain validation in Form Requests. Domain guards that require action-owned transactional state or dependent-record checks should live in the action and be mapped by the controller when a sibling action-delegation pattern does that; do not duplicate the same guard in the Form Request. Avoid `ValidationException::withMessages()` in web controllers unless a live sibling has the same controller-owned domain failure pattern. Current API session controllers may throw validation exceptions for external-token or session-domain failures.
- When a controller catches an action-owned domain exception and maps it to validation, the controller test that mocks the action only proves exception-to-validation mapping. Cover the action guard itself in `tests/Integration/Actions`.
- Name controller tests after the observable rejected behavior with verbs such as `rejects` or `prevents`; do not name them after the internal exception-to-validation mapping mechanism.

## Reference Map

Load only the references needed for the task:

- `references/database/migrations/README.md` for migration layout, indexes, route keys, soft deletes, and database inspection.
- `references/database/factories/README.md` for factory defaults, relationship factories, states, and after-creating hooks.
- `references/project/README.md` for routes, config, localization, bootstrap/public entrypoints, seeders, root tooling PHP, and auxiliary PHP guidance.
- `references/app/README.md` for patterns from the application layer under `app/`.
- `references/app/Actions/README.md` for action classes.
- `references/app/Console/Commands/README.md` for Artisan commands.
- `references/app/Enums/README.md` for enums.
- `references/app/Exceptions/README.md` for domain exceptions.
- `references/app/Http/Controllers/README.md` for controllers.
- `references/app/Http/Controllers/lifecycle-resources.md` for CRUDdy/resourceful controller naming, activation/deactivation, enable/disable, confirm/unconfirm, session/login/logout, regeneration, and other lifecycle actions.
- `references/app/Http/Middleware/README.md` for middleware.
- `references/app/Http/Requests/README.md` for form requests.
- `references/app/Http/Resources/README.md` for JSON resources.
- `references/app/Listeners/README.md` for event listeners.
- `references/app/Models/README.md` for Eloquent model structure, casts, docblocks, route keys, traits, and relationships.
- `references/app/Models/Concerns/README.md` for reusable model concerns.
- `references/app/Models/World/README.md` for models backed by a non-default reference-data connection.
- `references/app/Notifications/README.md` for notifications.
- `references/app/Policies/README.md` for policies.
- `references/app/Providers/README.md` for service providers.
- `references/app/Support/README.md` for support classes.
- `references/app/functions.php.md` for global helpers.
- `references/resources/views/README.md` for Inertia root Blade shells and hand-authored app views.
- `references/resources/react-email/README.md` for React Email templates, Nub commands, export lifecycle, generated Blade views, and mail assets.

Use the path-matched test references before editing or adding tests:

- `references/tests/README.md` for suite routing and the complete path map.
- `references/tests/Pest.md` and `references/tests/TestCase.md` for global Pest helpers and base test behavior.
- `references/tests/.pest/shards.md` for shard metadata expectations.
- `references/tests/ArchitectureTest.md` for architecture rules.
- `references/tests/Unit/Enums/README.md` for `tests/Unit/Enums/*Test.php`.
- `references/tests/Unit/Models/README.md` for `tests/Unit/Models/*Test.php`.
- `references/tests/Integration/Actions/README.md` for `tests/Integration/Actions/*Test.php`.
- `references/tests/Integration/Http/Resources/README.md` for `tests/Integration/Http/Resources/*ResourceTest.php`.
- `references/tests/Integration/Listeners/README.md` for `tests/Integration/Listeners/*Test.php`.
- `references/tests/Integration/Models/README.md` and `references/tests/Integration/Models/Concerns/README.md` for persisted model behavior.
- `references/tests/Integration/Support/Media/README.md` for media support integration tests.
- `references/tests/Feature/Console/README.md` for console command feature tests.
- `references/tests/Feature/Http/Controllers/README.md` for web controller feature tests.
- `references/tests/Feature/Http/Controllers/Api/README.md` for API controller feature tests.
- `references/tests/Feature/Http/Controllers/actions/*.md`, `modes/api-json.md`, `route-patterns.md`, and `validation/*.md` for controller action matrices.
- `references/tests/Feature/Http/Middleware/README.md` for middleware feature tests.
- `references/tests/Feature/Models/Concerns/README.md` for route-binding feature tests on model concerns.
- `references/tests/migrations/README.md` and `references/tests/testfiles/README.md` for test-only schema and binary/text fixtures.
- `references/tests/Support/Models/README.md` and `references/tests/TestSupport/README.md` for test-only support utilities.

## Completion Checklist

Before finalizing:

- New migration matches the local style and avoids unsupported rollback/FK patterns.
- New or changed model has typed relationships, casts, docblock properties, and the expected route-key/public-id behavior.
- Factory can create a valid row with realistic defaults and coherent relationship ownership.
- `resources/views` shell changes preserve Inertia head/app slots, Vite entrypoints, font directives, locale/html metadata, and production/authenticated third-party scripts.
- `resources/react-email` changes keep source templates under `resources/react-email/mail`, use Nub commands, and treat exported Blade views/assets as generated output.
- Tests are placed in the correct suite and cover every touched surface: unit-level configuration/pure logic, integration-level persisted behavior/resources/support, feature-level HTTP/console/middleware behavior, and browser coverage when real browser UX is touched.
- Related model/resource/controller tests are updated when system behavior or serialized contracts change; do not add paired model relationship tests just to prove Laravel relationship wiring.
- Controller coverage was checked against live nested siblings, not only action templates. If a nested child stores redundant `Workspace`/ancestor ownership, the controller tests include a same-parent mismatched-ownership `404` case and list actions exclude those records.
- Run the smallest relevant tests, for example:

```bash
php artisan test --compact tests/Unit/Models/<Model>Test.php tests/Integration/Models/<Model>Test.php
php artisan test --compact tests/Integration/Http/Resources/<Resource>Test.php
php artisan test --compact tests/Feature/Http/Controllers/<Controller>Test.php
```

- If PHP files changed, run:

```bash
vendor/bin/pint --dirty --format agent
```
