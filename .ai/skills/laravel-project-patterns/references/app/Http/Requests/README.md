# app/Http/Requests

## Purpose

This reference defines project conventions for controller validation and request normalization under `app/Http/Requests`.

## When To Use

Use this reference when creating or changing a Form Request, request-owned validation rule, request normalization path, API request, or controller feature test for validation.

## Required Pattern

Use `app/Http/Requests` for controller validation, request normalization, route-bound validation scope, request-owned cross-field validation, and API input contracts.

### Request Shape

- Use typed `rules(): array` methods with precise PHPDoc array shapes.
- Use `Rule::enum(...)`, scoped `Rule::unique(...)`, scoped `Rule::exists(...)`, `withoutTrashed()`, and `ignore($model)` where needed.
- Use route parameters through the same pattern as sibling requests. Prefer `#[RouteParameter(...)]` when a rule scopes to a bound parent or leaf model.
- When a request repeatedly needs a route-bound model, extract it through a small private helper that asserts the type.
- Store requests usually use `required`; update requests often use `sometimes|required`.
- Keep Form Requests focused on validation, normalization, and request-owned cross-field/domain validation. Do not add `input()` or `payload()` helpers when a Data input can be constructed directly from `$request->validated()` at the controller boundary.
- Application boot calls `FormRequest::failOnUnknownFields()`. If a field is not part of the endpoint contract, leave it out of `rules()` and let unknown-field validation reject submitted input.
- Add `missing` or `prohibited` only when the endpoint intentionally exposes a field-specific validation error for that submitted field. Do not add `exclude` to silently drop server-managed input.

Route-parameter helper pattern:

```php
private function parentRecord(): ParentRecord
{
    $parentRecord = $this->route('parent_record');

    assert($parentRecord instanceof ParentRecord);

    return $parentRecord;
}
```

Route-parameter injection pattern:

```php
/**
 * @return array<string, list<string|Stringable|ValidationRule>>
 */
public function rules(
    #[RouteParameter('workspace')] Workspace $workspace,
    #[RouteParameter('parent_record')] ParentRecord $parentRecord
): array {
    return [
        'name' => [
            'sometimes',
            'required',
            'string',
            'max:255',
            Rule::unique(ParentRecord::class)
                ->ignore($parentRecord)
                ->where('workspace_id', $workspace->id)
                ->withoutTrashed(),
        ],
    ];
}
```

Scoped uniqueness pattern:

```php
'name' => [
    'sometimes',
    'required',
    'string',
    'max:255',
    Rule::unique(ParentRecord::class)
        ->ignore($this->parentRecord())
        ->where('workspace_id', $this->workspace()->id)
        ->withoutTrashed(),
],
```

Public-ID selectable relation pattern:

```php
'related_record_id' => [
    'required',
    'string',
    Rule::exists(RelatedRecord::class, 'public_id')
        ->where('workspace_id', $this->workspace()->id)
        ->whereNull('deactivated_at')
        ->withoutTrashed(),
],
```

Conditional rule pattern:

```php
'dependent_amount' => [
    Rule::requiredIf($this->input('example_mode') === ExampleMode::Advanced->value),
    'nullable',
    'decimal:0,4',
    'gt:0',
],
'minimum_amount' => [
    'required',
    'decimal:0,4',
    'gte:0',
    Rule::when($this->filled('maximum_amount'), 'lte:maximum_amount'),
],
```

Contact and localized field pattern:

```php
'contact_email' => ['nullable', 'string', 'max:255', 'email:strict,dns', 'indisposable'],
'contact_phone_number' => [
    'nullable',
    'string',
    'max:255',
    new Phone()->country(CountryCode::values()),
],
'timezone' => ['sometimes', 'required', 'string', 'timezone'],
```

### Normalization

Use `prepareForValidation()` for derived input, partial-update normalization, and relationship inference. Do not add `prepareForValidation()` only to translate a valid public ID into an internal database ID. If validation can target the public ID column and the controller can resolve the model after validation, keep that conversion at the controller boundary.

Nullable field pairs use reciprocal `required_with`. On partial updates, add reciprocal `present_with` so explicitly submitting one side, including `null`, also requires the other key; omission of both remains valid.

Store nullable-pair rules:

```php
'start_value' => ['nullable', 'numeric', 'required_with:end_value'],
'end_value' => ['nullable', 'numeric', 'required_with:start_value'],
```

Partial-update nullable-pair rules:

```php
'start_value' => ['nullable', 'numeric', 'required_with:end_value', 'present_with:end_value'],
'end_value' => ['nullable', 'numeric', 'required_with:start_value', 'present_with:start_value'],
```

Address-like requests infer or clear `subdivision_code` from `region_code` during partial updates so subdivision validation still scopes to the effective region.

Address-like update normalization:

```php
#[Override]
protected function prepareForValidation(): void
{
    $childRecord = $this->childRecord();

    if ($this->filled('subdivision_code') && $this->isNotFilled('region_code')) {
        $this->merge([
            'region_code' => $childRecord->region_code->value,
        ]);
    }

    if (
        $this->filled('region_code')
        && $this->input('region_code') !== $childRecord->region_code->value
        && $this->isNotFilled('subdivision_code')
    ) {
        $this->merge([
            'subdivision_code' => null,
        ]);
    }
}
```

Partial min/max pairs should merge the missing side from the route-bound model when the submitted side needs it for validation:

```php
#[Override]
protected function prepareForValidation(): void
{
    $childRecord = $this->childRecord();

    if ($this->filled('minimum_amount') && $this->missing('maximum_amount') && $childRecord->maximum_amount !== null) {
        $this->merge([
            'maximum_amount' => $childRecord->maximum_amount,
        ]);
    }

    if ($this->filled('maximum_amount') && $this->missing('minimum_amount')) {
        $this->merge([
            'minimum_amount' => $childRecord->minimum_amount,
        ]);
    }
}
```

When a nullable upper bound can be explicitly cleared with `null`, use `has()` instead of `filled()` so the request still merges the stored lower bound for validation:

```php
#[Override]
protected function prepareForValidation(): void
{
    $leafRecord = $this->leafRecord();

    if ($this->filled('minimum_amount') && $leafRecord->maximum_amount !== null) {
        $this->mergeIfMissing(['maximum_amount' => $leafRecord->maximum_amount]);
    }

    if ($this->has('maximum_amount')) {
        $this->mergeIfMissing(['minimum_amount' => $leafRecord->minimum_amount]);
    }
}
```

Value normalization should be conservative: normalize only when the input is present and valid enough to transform safely.

Conservative contact normalization:

```php
#[Override]
protected function prepareForValidation(): void
{
    if ($this->isNotFilled('contact_phone_number')) {
        return;
    }

    $phoneNumber = new PhoneNumber($this->string('contact_phone_number')->toString(), CountryCode::values());

    if (! $phoneNumber->isValid()) {
        return;
    }

    try {
        $formattedPhoneNumber = $phoneNumber->formatE164();
    } catch (NumberParseException) {
        return;
    }

    $this->merge([
        'contact_phone_number' => $formattedPhoneNumber,
    ]);
}
```

### Cross-Field And Domain Validation

Use `after(): array` for cross-field and domain validation that needs model state and is safe to prove before the action runs. Return closures, first-class callables, or invokable validators that receive `Illuminate\Validation\Validator`. Keep callback bodies small; move multi-branch checks into private methods.

Short-circuit `after()` callbacks when base field errors already exist:

```php
/**
 * @return array<int, Closure(Validator): void>
 */
public function after(): array
{
    return [
        $this->validateActiveParentRecord(...),
    ];
}

private function validateActiveParentRecord(Validator $validator): void
{
    if ($validator->errors()->isNotEmpty()) {
        return;
    }

    if ($this->parentRecord()->deactivated_at !== null) {
        $validator->errors()->add(
            'parent_record',
            __('parent_record.validation.deactivated')
        );
    }
}
```

If a guard depends on transactional state, locks, or dependent-record checks owned by a delegated action, keep it out of the Form Request and map the action exception at the controller boundary.

For request-owned range-style domain validation, short-circuit `after()` callbacks when base field errors already exist, then add exact field errors for overlaps or duplicate open-ended ranges:

```php
private function validateRangeAvailability(Validator $validator): void
{
    if ($validator->errors()->isNotEmpty()) {
        return;
    }

    $leafRecord = $this->leafRecord();
    $minimumAmount = $this->float('minimum_amount', (float) $leafRecord->minimum_amount);
    $maximumAmount = $this->maximumAmount($leafRecord);

    if ($maximumAmount === null && $this->parentRecord()->leaves()->whereKeyNot($leafRecord)->whereNull('maximum_amount')->exists()) {
        $validator->errors()->add('maximum_amount', __('Only one open-ended range is allowed.'));
    }
}
```

Summary/general validation can use a named error bag when the entire payload is invalid rather than one field:

```php
throw ValidationException::withMessages([
    'summary' => __('Please provide at least one displayable value.'),
])->errorBag('_general');
```

### Server-Managed And Empty Requests

Use `missing` for fields that must not be submitted at all and `prohibited` for fields that should produce an explicit field error when submitted:

```php
'generated_code' => ['missing'],
'related_record_id' => ['prohibited'],
```

Do not create or keep empty Form Request classes just to give a controller method a named request type, to reject unknown payload fields, or to look consistent with store/update actions. If the controller does not consume validated input and the request has no request-owned hooks, use route models and action dependencies directly instead of type-hinting a useless request.

Empty request classes are valid only when they carry real request-owned behavior, such as authorization hooks, `prepareForValidation()`, `after()` validation, custom messages/attributes, or another explicit Form Request feature that the controller path actually needs:

```php
/**
 * @return array<string, list<string>>
 */
public function rules(): array
{
    return [];
}
```

### API Requests

- API session requests stay small and field-focused.
- External-token endpoints require token fields and any nonce/name fields the external contract needs.
- Access-code endpoints use strict DNS email validation plus the project indisposable rule.
- Code login endpoints validate code shape and existence separately from controller-owned expiration/used checks.

```php
/**
 * @return array<string, list<string>>
 */
public function rules(): array
{
    return [
        'recipient_email' => ['required', 'string', 'max:255', 'email:strict,dns', 'indisposable'],
        'code' => ['required', 'digits:6', Rule::exists(TemporaryCode::class)],
    ];
}
```

### Tests

- Request behavior is normally covered through controller feature tests.
- Do not add unit tests for removed or absent request `input()` or `payload()` helpers. If a request no longer owns action input transformation, cover validation in controller feature tests and input/persistence behavior in action integration tests.
- Assert exact validation messages in datasets when sibling tests do.
- Keep dataset payloads minimal and targeted to the failing rule.
- Validation tests must authenticate an authorized in-scope actor and use route parameters that pass binding; otherwise `403` or `404` will mask the validation contract.

## Coverage Expectations

Read the live request file, the controller that consumes it, and sibling request files for the same mode. Cover request behavior through the suite that owns the HTTP contract unless the logic belongs to an action integration test.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not move Data input construction into Form Request helpers when the controller boundary already owns it.
- Do not silently drop unknown or server-managed input.

## Related References

- `references/app/Http/Controllers/README.md`
- `references/app/Actions/README.md`
- `references/tests/Feature/Http/Controllers/README.md`
