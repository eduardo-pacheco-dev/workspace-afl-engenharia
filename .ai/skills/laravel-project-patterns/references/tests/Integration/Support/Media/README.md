# tests/Integration/Support/Media

## Purpose

This reference defines conventions for media support integration tests under `tests/Integration/Support/Media`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/Integration/Support/Media/<Class>Test.php` for media-library support classes such as path generators, file namers, and media metadata helpers.

### Common Setup

- Fake the configured disk with `Storage::fake('public')`.
- Create the generic test support model.
- Attach media with `UploadedFile::fake()->image(...)`.
- Assert media paths, file names, UUIDs, custom properties, or generated metadata.

### Path Generator Pattern

Cover:

- the media UUID is used as the base path;
- configured prefixes are included before the UUID path.

Use `Str::startsWith(...)` for path prefix contracts.

```php
it('uses the media uuid as the base path', function (): void {
    Storage::fake('public');

    $model = ExampleModel::query()->create();

    $media = $model->addMedia(UploadedFile::fake()->image('image.jpg'))
        ->toMediaCollection();

    expect(Str::startsWith($media->getPathRelativeToRoot(), $media->uuid.'/'))->toBeTrue();
});

it('includes the configured prefix before the media uuid path', function (): void {
    Storage::fake('public');

    config(['media-library.prefix' => 'uploads']);

    $model = ExampleModel::query()->create();

    $media = $model->addMedia(UploadedFile::fake()->image('image.jpg'))
        ->toMediaCollection();

    expect(Str::startsWith($media->getPathRelativeToRoot(), 'uploads/'.$media->uuid.'/'))->toBeTrue();
});
```

### File Namer Pattern

Cover:

- the filename base is a UUID;
- the original extension is preserved by the Media Library integration around the generated basename.

`FileNamer` itself returns the UUID basename; the package appends/preserves the extension when storing media. Use `pathinfo(...)` and `Str::isUuid(...)` for assertions.

```php
it('uses a uuid file name when media is added', function (): void {
    Storage::fake('public');

    $model = ExampleModel::query()->create();

    $media = $model->addMedia(UploadedFile::fake()->image('photo.png'))
        ->toMediaCollection();

    expect(Str::isUuid(pathinfo($media->file_name, PATHINFO_FILENAME)))->toBeTrue()
        ->and(pathinfo($media->file_name, PATHINFO_EXTENSION))->toBe('png');
});
```

### Do Not

- Do not hit real disks or cloud storage.
- Do not use application models when the behavior is generic to media support.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/Support/README.md`
- `references/app/Listeners/README.md`
