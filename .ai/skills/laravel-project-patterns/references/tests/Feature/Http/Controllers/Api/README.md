# tests/Feature/Http/Controllers/Api

## Purpose

This reference defines conventions for JSON API controller feature tests.

## When To Use

Use this reference for tests under `tests/Feature/Http/Controllers/Api/**`.

## Required Pattern

Load `../modes/api-json.md` for transport rules and keep the same entry-point discipline as web controller tests.

### File Shape

- Use `getJson()` and `postJson()` for existing API routes.
- Public session endpoints use flat `it(...)` tests when there is one invokable flow.
- Assert validation with `assertUnprocessable()` and `assertJsonValidationErrors([...])`.
- Use datasets for validation matrices.
- Use `AssertableJson` when response shape includes dynamic tokens or nested resources.
- Assert side effects explicitly when token, external identity, actor, access-code, or notification state is part of the contract.

### Authenticated API Pattern

For protected endpoints:

1. guest request asserts `assertUnauthorized()`;
2. authenticated request uses the shared login helper;
3. response asserts public IDs, not internal IDs.

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

### Public Session Endpoints

For public endpoints such as `api.sessions.code.request`, `api.sessions.code.login`, `api.sessions.identity.login`, and `api.sessions.secondary-identity.login`, do not add an unauthenticated failure test unless the route becomes protected. Keep tests focused on public validation, external verification, domain branches, success JSON, and side effects.

### Endpoint Matrix and Order

| Route                                        | Observed order                                                                                                                                                                                                                                                                                                                                                               |
| -------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `POST api.sessions.identity.login`           | validation dataset; existing external identity; existing identity with missing email claim when allowed; changed email for same external ID; existing email conflict; external ID conflict through same email; account creation; missing email for new account; verification failures such as invalid token, invalid audience, expired token, invalid issuer, nonce mismatch |
| `POST api.sessions.secondary-identity.login` | validation dataset; token verification failure; existing external identity; changed email for same external ID; existing email conflict; external ID conflict through same email; account creation                                                                                                                                                                           |
| `POST api.sessions.code.request`             | validation dataset; generated-code action invocation; notification dispatch                                                                                                                                                                                                                                                                                                  |
| `POST api.sessions.code.login`               | validation dataset; expired code failure; used code failure; valid code creates an actor; valid code authenticates an existing actor                                                                                                                                                                                                                                         |
| `GET api.profile.show`                       | unauthenticated `401`; authenticated success with serialized actor public ID                                                                                                                                                                                                                                                                                                 |

Public session ordering starts with validation because no auth middleware should block those routes. Domain failures follow validation and precede success unless a sibling file establishes a more specific flow order. Protected actor endpoints start with unauthenticated access and then success.

### External Identity Endpoints

For external identity endpoints:

- fake token/key verification through the same mechanism sibling tests use;
- cover verification failures such as invalid token, invalid audience, expired token, invalid issuer, and nonce mismatch when supported;
- cover existing identity login;
- cover missing required claims when the controller handles that branch;
- cover same external ID with changed email;
- cover existing email conflicts;
- cover external ID conflicts;
- cover account creation;
- assert token count and verified email state when relevant.

### Access-Code Endpoints

For access-code request/login endpoints:

- request endpoints prove validation, action invocation, and notification dispatch;
- login endpoints reject expired and already-used codes before success;
- success marks the code as used, authenticates or creates the actor as required, and asserts token response shape.

### Validation Dataset Pattern

```php
it('validates fields', function (array $data, array $expected): void {
    $response = postJson(route('api.route'), $data);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors($expected);
})->with([
    'required' => [
        'data' => [],
        'expected' => ['id_token' => 'The id token field is required.'],
    ],
]);
```

## Coverage Expectations

Use the live controller, routes, form requests, resources, and sibling tests to decide the complete API matrix. Preserve examples, but keep them synthetic and only implement routes that exist.

## Do Not

- Do not use web helpers for API endpoints.
- Do not assert only status when side effects are the contract.
- Do not use real external service, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
