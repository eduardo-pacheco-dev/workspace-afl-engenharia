# app/Support

## Purpose

This reference defines project conventions for small infrastructure helpers and package extension points under `app/Support`.

## When To Use

Use this reference when creating or changing package hooks, media helpers, storage/path helpers, or small support classes that do not belong under controllers, models, requests, resources, or actions.

## Required Pattern

Use `app/Support` for small infrastructure helpers and package extension points.

### Support Shape

- Keep support classes narrow and framework-integrated.
- Use typed methods and explicit return types.
- Prefer framework abstractions for files, media, HTTP, paths, and storage.
- Use `#[Override]` when extending package hooks such as media file namers and path generators.
- Keep support classes free of application-model assumptions when the helper is package-level infrastructure.

Media file naming pattern:

```php
class FileNamer extends DefaultFileNamer
{
    #[Override]
    public function originalFileName(string $fileName): string
    {
        return (string) Str::uuid();
    }
}
```

The file namer owns the UUID basename only. The package combines that basename with the uploaded file extension, so tests may assert extension preservation without implying the file namer returns the extension itself.

Media path pattern:

```php
class PathGenerator extends DefaultPathGenerator
{
    #[Override]
    protected function getBasePath(Media $media): string
    {
        $prefix = Config::string('media-library.prefix', '');

        if ($prefix !== '') {
            return $prefix.'/'.$media->uuid;
        }

        return $media->uuid;
    }
}
```

### Test Mapping

- Choose the test path by the helper's actual integration point.
- Use the same kind of fakes as sibling tests for the touched support area.
- Assert the exact path, file name, metadata, payload, or value the support class owns.
- For media support, use storage fakes, uploaded-file fakes, and test support models.

## Coverage Expectations

Read the live support file, package hook contract, and sibling tests for the touched support area. Cover the exact integration point the support class owns.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not add broad utility classes when a framework helper, typed class, or domain-specific service would be clearer.

## Related References

- `references/tests/Integration/Support/Media/README.md`
- `references/app/Listeners/README.md`
- `references/project/README.md`
