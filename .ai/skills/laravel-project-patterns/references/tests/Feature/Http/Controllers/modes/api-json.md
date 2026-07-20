# API/JSON Mode for Controller Tests

## Purpose

This reference defines JSON/API adaptations for controller feature tests.

## When To Use

Use this reference when the controller test flow uses `getJson()`, `postJson()`, `patchJson()`, `deleteJson()`, JSON validation errors, token responses, public session endpoints, or protected JSON resources.

## Required Pattern

Inspect the route, controller action, form request, resource, and response helpers before adapting any template. JSON assertions must match the real envelope and field names.

Canonical status rule:

- Policy denial -> `assertForbidden()` (`403`).
- Binding mismatch -> `assertNotFound()` (`404`).
- Guest protected endpoint -> `assertUnauthorized()` (`401`).
- Validation failure -> `assertUnprocessable()->assertJsonValidationErrors([...])`.

For validation, assert exact messages instead of keys only.

### Request and Assertion Mapping

| Web/session                          | JSON API                                                         |
| ------------------------------------ | ---------------------------------------------------------------- |
| `get`, `post`, `patch`, `delete`     | `getJson`, `postJson`, `patchJson`, `deleteJson`                 |
| guest web endpoint -> login redirect | protected JSON endpoint -> `assertUnauthorized()`                |
| `assertRedirectBackWithErrors(...)`  | `assertUnprocessable()->assertJsonValidationErrors(...)`         |
| `assertOk() + assertInertia(...)`    | `assertOk()/assertCreated()/assertNoContent() + assertJson(...)` |
| redirect + toast                     | JSON payload/status contract                                     |

### Protected vs Public Endpoints

- Protected endpoint: add `requires authentication`, use `assertUnauthorized()`, then authenticate and assert public IDs in JSON.
- Public endpoint: do not add auth-required tests unless middleware protects it. Start with validation, then domain failures, then success and side effects.

Current public examples use neutral route shapes:

- `api.sessions.identity.login`
- `api.sessions.secondary-identity.login`
- `api.sessions.code.request`
- `api.sessions.code.login`

Protected actor example:

- `api.profile.show`

Do not infer JSON API equivalents for web routes unless the API route file exposes them.

### Public Endpoint Template

```php
it('validates fields', function (array $data, array $expected): void {
    $response = postJson(route('api.sessions.code.request'), $data);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors($expected);
})->with([
    'recipient email' => [
        'data' => [
            'recipient_email' => 'invalid',
        ],
        'expected' => [
            'recipient_email' => 'The recipient email field must be a valid email address.',
        ],
    ],
]);
```

### Protected Endpoint Template

```php
describe('show', function (): void {
    it('requires authentication', function (): void {
        $response = getJson(route('api.profile.show'));

        $response->assertUnauthorized();
    });

    it('shows the authenticated actor', function (): void {
        $actor = Actor::factory()->createOne();

        login(actor: $actor);

        $response = getJson(route('api.profile.show'));

        $response->assertOk()
            ->assertJsonPath('id', $actor->public_id);
    });
});
```

### JSON Store Example

```php
describe('store', function (): void {
    it('requires authentication', function (): void {
        $workspace = Workspace::factory()->createOne();

        $response = postJson(route('workspaces.parent-records.store', [
            'workspace' => $workspace,
        ]));

        $response->assertUnauthorized();
    });

    it('validates fields', function (array $data, array $expected): void {
        $workspace = Workspace::factory()->createOne();

        login(workspace: $workspace);

        $response = postJson(route('workspaces.parent-records.store', [
            'workspace' => $workspace,
        ]), $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors($expected);
    })->with([
        'required' => [
            'data' => [],
            'expected' => [
                'name' => 'The name field is required.',
            ],
        ],
    ]);

    it('creates a parent record', function (): void {
        $workspace = Workspace::factory()->createOne();

        login(workspace: $workspace);

        $response = postJson(route('workspaces.parent-records.store', [
            'workspace' => $workspace,
        ]), [
            'name' => 'Example Parent',
        ]);

        $response->assertCreated()
            ->assertJson(function (AssertableJson $json): void {
                $json
                    ->has('id')
                    ->where('name', 'Example Parent')
                    ->etc();
            });

        assertDatabaseHas(ParentRecord::class, [
            'workspace_id' => $workspace->id,
            'name' => 'Example Parent',
        ]);
    });
});
```

### JSON Update Example

```php
describe('update', function (): void {
    it('returns not found when parent record belongs to another Workspace', function (): void {
        $workspace = Workspace::factory()->createOne();
        $parentRecord = ParentRecord::factory()->createOne();

        login(workspace: $workspace);

        $response = patchJson(route('workspaces.parent-records.update', [
            'workspace' => $workspace,
            'parent_record' => $parentRecord,
        ]), [
            'name' => 'Updated',
        ]);

        $response->assertNotFound();
    });

    it('updates a parent record', function (): void {
        $parentRecord = ParentRecord::factory()->createOne();

        login(workspace: $parentRecord->workspace);

        $response = patchJson(route('workspaces.parent-records.update', [
            'workspace' => $parentRecord->workspace,
            'parent_record' => $parentRecord,
        ]), [
            'name' => 'Updated Parent',
        ]);

        $response->assertOk()
            ->assertJsonPath('name', 'Updated Parent');
    });
});
```

## Coverage Expectations

Use the live controller, API route file, form request, policy, resource, and sibling tests to decide the complete JSON matrix. For current public session-style endpoints, prioritize validation, domain failures, success JSON, and side effects. Apply resource-style JSON examples only to routes that actually exist.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not drop applicable JSON transport coverage when adapting examples.
- Do not use real module, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
