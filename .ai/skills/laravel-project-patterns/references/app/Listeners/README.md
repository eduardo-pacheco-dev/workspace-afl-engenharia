# app/Listeners

## Purpose

Define conventions for application event listeners.

## When To Use

Use this reference before creating or changing listeners under `app/Listeners`, especially listeners attached to framework or package events.

## Required Pattern

Use listeners for focused side effects that are naturally owned by an event emitted by Laravel or a package.

### Listener Shape

- Keep listeners narrow and event-specific.
- Type the event parameter on `handle(...)` and return `void`.
- Use framework/package APIs instead of re-querying broad application state.
- Guard unsupported event payloads early.
- Catch and report recoverable package-processing failures only when the listener must not break the originating workflow. Use the configured error-reporting package for those reports.
- Do not hide domain workflow decisions in listeners when an explicit action, request, policy, or controller should own the decision.

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use function Sentry\captureException;

use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Throwable;

class ExampleMediaMetadataListener
{
    public function handle(MediaHasBeenAddedEvent $event): void
    {
        try {
            if (! in_array(Str::lower($event->media->mime_type), ['image/jpeg', 'image/jpg', 'image/png'], true)) {
                return;
            }

            $manager = new ImageManager(new Driver());
            $image = $manager->decodeStream($event->media->stream());

            $event->media->setCustomProperty('height', $image->height());
            $event->media->setCustomProperty('width', $image->width());

            $event->media->save();
        } catch (Throwable $e) {
            captureException($e);
        }
    }
}
```

### Test Mapping

- Listener behavior belongs under `tests/Integration/Listeners`.
- Trigger the package or framework event path instead of calling listener internals directly.
- For media listeners, attach media through the model API with `UploadedFile::fake()`, then assert persisted custom properties on the returned media record.
- Cover both the positive side effect and the unsupported branch.

## Coverage Expectations

Read the live listener, the provider/package registration path if relevant, and sibling listener tests before adding adjacent coverage. Cover the event-triggered side effect the listener owns; do not add tests just to prove the framework dispatches events.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not create observer guidance from listener evidence. Observers are not a current live path in this project.

## Related References

- `references/tests/Integration/Listeners/README.md`
- `references/app/Support/README.md`
- `references/project/README.md`
