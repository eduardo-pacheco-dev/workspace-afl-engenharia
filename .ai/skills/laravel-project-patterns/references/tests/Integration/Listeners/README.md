# tests/Integration/Listeners

## Purpose

This reference defines conventions for integration tests under `tests/Integration/Listeners`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/Integration/Listeners/<Listener>Test.php` for event/listener behavior that depends on model events, media events, persistence, or framework integration.

### File Shape

- Create the minimum persisted model that triggers the listener. Use the generic support model from `tests/Support/Models` for package listeners when no application model owns the behavior.
- Use fakes for files, storage, media, notifications, or HTTP as needed.
- Assert the persisted side effect on the model, media record, custom property, database, or dispatched output.

### Media Listener Pattern

For media listeners:

- use `UploadedFile::fake()->image(...)`;
- attach media through the model API that triggers the package event;
- assert custom properties or generated metadata on the returned media instance;
- include a negative test for unsupported mime types or unsupported conditions.

```php
it('only runs for supported mime types', function (): void {
    $model = ExampleModel::query()->create();

    $media = $model->addMedia(UploadedFile::fake()->image('image.gif'))
        ->toMediaCollection();

    expect($media->getCustomProperty('height'))->toBeNull()
        ->and($media->getCustomProperty('width'))->toBeNull();
});

it('saves generated metadata for supported images', function (): void {
    $model = ExampleModel::query()->create();

    $media = $model->addMedia(UploadedFile::fake()->image('image.jpg', 200, 200))
        ->toMediaCollection();

    expect($media->getCustomProperty('height'))->toBe(200)
        ->and($media->getCustomProperty('width'))->toBe(200);
});
```

## Coverage Expectations

Cover both:

- the listener runs and writes the expected side effect;
- the listener does not run for unsupported input.

Do not call listener internals directly when a package or framework event path is the behavior being protected.
Listeners use narrower coverage wording than generic references because the protected behavior is the event path that invokes the listener. Read the live listener and sibling listener tests before adding adjacent coverage; do not add symmetry tests when the listener does not own that behavior.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/Listeners/README.md`
- `references/app/Support/README.md`
