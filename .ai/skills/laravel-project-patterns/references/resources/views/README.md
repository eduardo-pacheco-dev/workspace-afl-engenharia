# resources/views

## Purpose

This reference defines conventions for hand-authored Blade views under `resources/views`.

## When To Use

Use this reference before creating or changing the root Inertia shell, hand-authored application Blade views, Vite entrypoint wiring, document-level metadata, or production-only scripts embedded in Blade.

## Required Pattern

`resources/views/app.blade.php` is the root Inertia document shell. Keep it small and document-level:

- Set the `<html lang>` from the application locale.
- Keep charset, viewport, favicon, and theme-color metadata in the head.
- Load local font aliases through the existing font directive.
- Keep `@viteReactRefresh` and the Vite application entrypoint together.
- Keep the Inertia head component in `<head>` and the Inertia app component in `<body>`.
- Preserve body classes that establish global background, foreground, font, and antialiasing defaults.
- Gate production-only third-party browser scripts behind both production and authenticated-session checks when the live shell does that.
- Do not move page data, shared props, or route-level decisions into Blade. Those belong to Inertia middleware, controllers, resources, and the frontend entrypoint.

Root shell shape:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', Config::string('app.locale')) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <link rel="icon" href="{{ url('favicon.ico') }}" sizes="any" />

        <meta name="theme-color" content="#ffffff" />

        @fonts('sans')

        @viteReactRefresh
        @vite('resources/js/app.tsx')

        <x-inertia::head>
            <title>Application</title>
        </x-inertia::head>
    </head>

    <body class="bg-background text-foreground font-sans antialiased">
        <x-inertia::app />

        @production
            @auth
                <script>
                    window.ExampleWidget?.('init', {widgetId: 'example-widget-id'})
                </script>
            @endauth
        @endproduction
    </body>
</html>
```

When touching the Vite entrypoint or Inertia shell together, keep the boundary clear:

- Blade owns document metadata, font/Vite directives, root Inertia slots, and production script gates.
- The frontend entrypoint owns layout resolution, progress color, page resolution, strict mode, toast handling, providers, and client-side SDK initialization.
- Inertia middleware owns shared props such as authenticated actor and `Workspace` data.
- Vite config owns the application input path, font alias registration, refresh behavior, and plugin setup.

## Coverage Expectations

Read the Blade shell, Vite config, frontend entrypoint, and Inertia middleware before changing the root app shell. Use feature or browser-level verification only when the changed behavior is visible through HTTP, page rendering, authentication state, or loaded assets.

## Do Not

- Do not use real application, vendor widget, analytics, or monitoring names in examples.
- Do not hand-author React Email generated views here; use `references/resources/react-email/README.md` for mail templates and generated mail Blade output.
- Do not move controller, middleware, shared-prop, or frontend layout behavior into the Blade shell.

## Related References

- `SKILL.md`
- `references/app/Http/Middleware/README.md`
- `references/app/Http/Controllers/README.md`
- `references/resources/react-email/README.md`
- `references/project/README.md`
