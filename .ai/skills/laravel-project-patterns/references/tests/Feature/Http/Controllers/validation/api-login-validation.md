# API Session Validation Snippets

## Purpose

This reference defines validation snippets for public JSON authentication endpoints.

## When To Use

Use this reference for public API session, external identity, access-code request, and access-code login endpoints. Do not add `requires authentication` tests unless the route is protected.

## Required Pattern

Validation datasets for public session endpoints are intentionally compact. Verification failures, identity conflicts, expired codes, used codes, and account creation are domain cases after validation.

Suggested flow order:

- external identity primary: validation, existing identity success, missing contact address for linked identity when supported, changed contact address for same external ID, existing contact-address conflict, external ID conflict, account creation, missing contact address for new account, then verification failures;
- external identity secondary: validation, verification failure, existing identity success, changed contact address for same external ID, existing contact-address conflict, external ID conflict, account creation;
- access-code request: validation, generated-code action invocation, notification dispatch;
- access-code login: validation, expired code, used code, create actor, existing actor.

### `POST /api/session-codes/request` (`api.sessions.code.request`)

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
    'email:dns' => [
        'data' => [
            'recipient_email' => 'test@example.invalid',
        ],
        'expected' => [
            'recipient_email' => 'The recipient email field must be a valid email address.',
        ],
    ],
    'email:strict' => [
        'data' => [
            'recipient_email' => 'test()@example.test',
        ],
        'expected' => [
            'recipient_email' => 'The recipient email field must be a valid email address.',
        ],
    ],
    'indisposable' => [
        'data' => [
            'recipient_email' => 'test@discarded.example',
        ],
        'expected' => [
            'recipient_email' => "This email address can't be used. Please try a different email.",
        ],
    ],
    'max:255 (string)' => [
        'data' => [
            'recipient_email' => str_repeat('a', 256),
        ],
        'expected' => [
            'recipient_email' => 'The recipient email field must not be greater than 255 characters.',
        ],
    ],
    'required' => [
        'data' => [],
        'expected' => [
            'recipient_email' => 'The recipient email field is required.',
        ],
    ],
]);
```

### `POST /api/session-codes/login` (`api.sessions.code.login`)

```php
it('validates fields', function (array $data, array $expected): void {
    $response = postJson(route('api.sessions.code.login'), $data);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors($expected);
})->with([
    'digits:6' => [
        'data' => [
            'recipient_email' => 'actor@example.test',
            'code' => '12345',
        ],
        'expected' => [
            'code' => 'The code field must be 6 digits.',
        ],
    ],
    'recipient email' => [
        'data' => [
            'recipient_email' => 'invalid',
        ],
        'expected' => [
            'recipient_email' => 'The recipient email field must be a valid email address.',
        ],
    ],
    'exists' => [
        'data' => [
            'code' => '111111',
        ],
        'expected' => [
            'code' => 'The selected code is invalid.',
        ],
    ],
    'required' => [
        'data' => [],
        'expected' => [
            'code' => 'The code field is required.',
            'recipient_email' => 'The recipient email field is required.',
        ],
    ],
]);
```

### `POST /api/session-identities/login` (`api.sessions.identity.login`)

```php
it('validates fields', function (array $data, array $expected): void {
    $response = postJson(route('api.sessions.identity.login'), $data);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors($expected);
})->with([
    'max:255 (string)' => [
        'data' => [
            'first_name' => str_repeat('a', 256),
            'last_name' => str_repeat('b', 256),
        ],
        'expected' => [
            'first_name' => 'The first name field must not be greater than 255 characters.',
            'last_name' => 'The last name field must not be greater than 255 characters.',
        ],
    ],
    'required' => [
        'data' => [],
        'expected' => [
            'id_token' => 'The id token field is required.',
            'nonce' => 'The nonce field is required.',
        ],
    ],
]);
```

## Coverage Expectations

Use the live controller, routes, form requests, resources, and sibling tests to decide the complete auth matrix. Request endpoints also prove action invocation and notification dispatch. Login endpoints cover expired codes, used codes, new-actor success, existing-actor success, token response shape, and persisted `used_at`/contact verification side effects.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not drop applicable validation coverage when adapting examples.
- Do not use real external service, route, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
