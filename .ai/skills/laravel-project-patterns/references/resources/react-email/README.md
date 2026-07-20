# resources/react-email

## Purpose

Document the `resources/react-email` contract for React Email templates, export scripts, generated Blade views, and mail assets.

## When To Use

Use this reference before creating or changing React Email templates, static email assets, React Email scripts, or generated mail views/assets.

## Required Pattern

- The React Email source area is a Nub package at `resources/react-email`.
- Email source components belong in `resources/react-email/mail/`. Name components to match the Blade view path they should generate under `resources/views/mail`.
- Static email assets belong under `resources/react-email/mail/static/`.
- Use `resources/react-email/mail/_utils/resolve-email-asset-path.ts` for image/static asset paths. It returns `/static/...` during preview and a Laravel `url("/assets/mail/...")` Blade expression during export.
- Preview templates with `nub run dev` from `resources/react-email`.
- Export templates with `nub run export` from `resources/react-email`.
- The export lifecycle runs `preexport`, `export`, then `postexport`:
  - `preexport` clears and recreates `resources/views/mail` and clears `public/assets/mail`.
  - `export` runs `EXPORT=true email export --dir mail --outDir ../views/mail --pretty`.
  - `postexport` renames exported `.html` files to `.blade.php` and moves exported `static/` assets to `public/assets/mail`.
- Reference exported Blade views from Laravel mailables or notifications; do not hand-author the generated Blade output when a React Email source component should own it.
- When the package only has scaffold files, static placeholders, and export scripts, keep the reference limited to those scaffold/export contracts. Do not invent template layout, copy, styling, or component rules until a committed source template and exported Blade output exist.

### Package Scripts Example

```json
{
  "name": "example-emails",
  "private": true,
  "scripts": {
    "build": "email build --dir mail",
    "dev": "email dev --dir mail",
    "export": "EXPORT=true email export --dir mail --outDir ../views/mail --pretty",
    "postexport": "nub scripts/finalize-email-export.mjs",
    "preexport": "nub scripts/prepare-email-export.mjs"
  }
}
```

### Asset Path Example

```ts
export function resolveEmailAssetPath(path: string): string {
  if (process.env.EXPORT !== 'true') {
    return `/static/${path}`
  }

  return `{{ url("/assets/mail/${path}") }}`
}
```

### Export Script Example

```js
import {mkdir, readdir, rename, rm, stat} from 'node:fs/promises'
import {basename, join} from 'node:path'

import {mailViewsDir, publicAssetsDir, publicAssetsMailDir} from './config.mjs'

await rm(mailViewsDir, {force: true, recursive: true})
await rm(publicAssetsMailDir, {force: true, recursive: true})

await mkdir(mailViewsDir, {recursive: true})
await mkdir(publicAssetsDir, {recursive: true})

async function renameHtmlFiles(directory) {
  const entries = await readdir(directory, {withFileTypes: true})

  await Promise.all(
    entries.map(async (entry) => {
      const entryPath = join(directory, entry.name)

      if (entry.isDirectory()) {
        await renameHtmlFiles(entryPath)
        return
      }

      if (!entry.isFile() || !entry.name.endsWith('.html')) {
        return
      }

      const bladePath = join(directory, `${basename(entry.name, '.html')}.blade.php`)

      await rm(bladePath, {force: true})
      await rename(entryPath, bladePath)
    }),
  )
}

await renameHtmlFiles(mailViewsDir)

const staticDir = join(mailViewsDir, 'static')

try {
  const staticStats = await stat(staticDir)

  if (staticStats.isDirectory()) {
    await mkdir(publicAssetsDir, {recursive: true})
    await rm(publicAssetsMailDir, {recursive: true, force: true})
    await rename(staticDir, publicAssetsMailDir)
  }
} catch (error) {
  if (error?.code !== 'ENOENT') {
    throw error
  }
}
```

## Coverage Expectations

- If adding the first real template pattern, inspect the generated Blade output after export before documenting broad styling rules.
- When a template uses static assets, verify both preview paths and exported Blade asset paths through `resolveEmailAssetPath`.
- If Laravel mailables or notifications are changed to consume exported views, cover that behavior with the nearest mail/notification tests.
- Keep export-related changes scoped to `resources/react-email/**`, `resources/views/mail/**`, and `public/assets/mail/**` unless the task explicitly requires Laravel mail integration.

## Do Not

- Do not hand-edit `resources/views/mail/**` as the source of truth for React Email-backed templates.
- Do not store mail assets directly in `public/assets/mail` as source files; export moves assets there from `mail/static`.
- Do not bypass the `preexport` cleanup behavior when rebuilding exported views, because stale generated views or assets can survive otherwise.
- Do not edit `resources/react-email/.react-email/**`; it is preview-server generated output and is ignored by the package area.
- Do not create generic React Email style rules without a committed source template and exported Blade output to inspect.
- Do not use `npx` for one-off React Email commands; use Nub or `nubx` in this repository.

## Related References

- `SKILL.md`
- `references/resources/views/README.md`
- `references/app/Notifications/README.md`
- `references/project/README.md`
