# Route Patterns for Controller Tests

## Purpose

This reference defines route-name, route-parameter, and scoped-binding conventions for controller feature tests.

## When To Use

Use this reference when a controller test calls `route(...)`, passes nested route parameters, or needs `403` versus `404` boundary coverage.

## Required Pattern

### Action Set

Controller tests in this style commonly cover these action groups when present:

- `create`
- `destroy`
- `edit`
- `index`
- `show`
- `store`
- `update`

The route file determines the active actions. Do not invent missing routes.

### Route Surface Shape

Authenticated web routes are normally scoped by `Workspace` and use `scopeBindings()` for nested chains. Encode the real route shape before writing a test.

| Shape                       | Route names                                                                                               | Parameter notes                                                                                        |
| --------------------------- | --------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| Settings or singleton route | `workspaces.settings.general`, `workspaces.update`                                                        | one `Workspace` route parameter when the route is `Workspace`-bound                                    |
| Two-resource chain          | `workspaces.parent-records.*`                                                                             | collection actions pass `workspace`; member actions pass `workspace`, `parent_record`                  |
| Three-resource chain        | `workspaces.parent-records.children.*`                                                                    | collection actions pass `workspace`, `parent_record`; member actions add `child_record`                |
| Four-resource chain         | `workspaces.parent-records.children.leaves.*`                                                             | collection actions pass `workspace`, `parent_record`, `child_record`; member actions add `leaf_record` |
| Invokable nested action     | `workspaces.parent-records.children.make-default`                                                         | pass every ancestor plus the child/leaf target                                                         |
| JSON API route              | `api.sessions.identity.login`, `api.sessions.code.request`, `api.sessions.code.login`, `api.profile.show` | use JSON helpers and the route's auth middleware contract                                              |

### Binding Order

For collection actions under a deep chain, use this order:

1. guest/auth failure;
2. authorized actor missing `Workspace` access -> `403`;
3. parent belongs to another Workspace -> `404`;
4. parent is soft deleted -> `404`;
5. child belongs to another parent in the same Workspace -> `404`;
6. child belongs to another Workspace -> `404`;
7. child is soft deleted -> `404`;
8. lifecycle/state guard if create/list/update is blocked or allowed by contract;
9. validation or success/list assertions.

For member actions under a deep chain, append leaf checks:

1. leaf belongs to another direct parent -> `404`;
2. leaf belongs to another ancestor graph in the same Workspace -> `404`;
3. leaf belongs to another Workspace -> `404`;
4. leaf is soft deleted -> `404`;
5. lifecycle/state guard if the member action is blocked or allowed by contract.

### Route Parameter Keys

Always derive parameter keys from the real route definition. The snippets below use synthetic keys:

- `workspace`
- `parent_record`
- `child_record`
- `leaf_record`

When `scopeBindings()` is active, mismatched chains are binding failures and should be asserted as `404`, not policy denials.

### Settings or Singleton Route

```php
$response = get(route('workspaces.settings.general', [
    'workspace' => $workspace,
]));

$response = patch(route('workspaces.update', [
    'workspace' => $workspace,
]), [
    'name' => 'Example Workspace',
]);
```

### Two-Resource Route Chain

| Action  | Route name                          | Parameters                   |
| ------- | ----------------------------------- | ---------------------------- |
| index   | `workspaces.parent-records.index`   | `workspace`                  |
| create  | `workspaces.parent-records.create`  | `workspace`                  |
| store   | `workspaces.parent-records.store`   | `workspace`                  |
| show    | `workspaces.parent-records.show`    | `workspace`, `parent_record` |
| edit    | `workspaces.parent-records.edit`    | `workspace`, `parent_record` |
| update  | `workspaces.parent-records.update`  | `workspace`, `parent_record` |
| destroy | `workspaces.parent-records.destroy` | `workspace`, `parent_record` |

```php
$response = get(route('workspaces.parent-records.index', [
    'workspace' => $workspace,
]));

$response = patch(route('workspaces.parent-records.update', [
    'workspace' => $parentRecord->workspace,
    'parent_record' => $parentRecord,
]), [
    'name' => 'Updated',
]);
```

### Three-Resource Route Chain

| Action  | Route name                                   | Parameters                                   |
| ------- | -------------------------------------------- | -------------------------------------------- |
| index   | `workspaces.parent-records.children.index`   | `workspace`, `parent_record`                 |
| create  | `workspaces.parent-records.children.create`  | `workspace`, `parent_record`                 |
| store   | `workspaces.parent-records.children.store`   | `workspace`, `parent_record`                 |
| show    | `workspaces.parent-records.children.show`    | `workspace`, `parent_record`, `child_record` |
| edit    | `workspaces.parent-records.children.edit`    | `workspace`, `parent_record`, `child_record` |
| update  | `workspaces.parent-records.children.update`  | `workspace`, `parent_record`, `child_record` |
| destroy | `workspaces.parent-records.children.destroy` | `workspace`, `parent_record`, `child_record` |

```php
$response = get(route('workspaces.parent-records.children.index', [
    'workspace' => $parentRecord->workspace,
    'parent_record' => $parentRecord,
]));

$response = delete(route('workspaces.parent-records.children.destroy', [
    'workspace' => $childRecord->parentRecord->workspace,
    'parent_record' => $childRecord->parentRecord,
    'child_record' => $childRecord,
]));
```

### Four-Resource Route Chain

| Action  | Route name                                          | Parameters                                                  |
| ------- | --------------------------------------------------- | ----------------------------------------------------------- |
| index   | `workspaces.parent-records.children.leaves.index`   | `workspace`, `parent_record`, `child_record`                |
| create  | `workspaces.parent-records.children.leaves.create`  | `workspace`, `parent_record`, `child_record`                |
| store   | `workspaces.parent-records.children.leaves.store`   | `workspace`, `parent_record`, `child_record`                |
| show    | `workspaces.parent-records.children.leaves.show`    | `workspace`, `parent_record`, `child_record`, `leaf_record` |
| edit    | `workspaces.parent-records.children.leaves.edit`    | `workspace`, `parent_record`, `child_record`, `leaf_record` |
| update  | `workspaces.parent-records.children.leaves.update`  | `workspace`, `parent_record`, `child_record`, `leaf_record` |
| destroy | `workspaces.parent-records.children.leaves.destroy` | `workspace`, `parent_record`, `child_record`, `leaf_record` |

```php
$response = get(route('workspaces.parent-records.children.leaves.show', [
    'workspace' => $leafRecord->childRecord->parentRecord->workspace,
    'parent_record' => $leafRecord->childRecord->parentRecord,
    'child_record' => $leafRecord->childRecord,
    'leaf_record' => $leafRecord,
]));
```

### N-Level Nested Resource Rule

If routes nest deeper than the examples:

1. pass every ancestor parameter in route order;
2. add mismatch tests for every ancestor, direct parent, and leaf;
3. add soft-delete checks for every model using soft deletes;
4. add same-`Workspace` wrong-parent graph checks when those records can exist;
5. add redundant ownership mismatch checks when a child stores both a direct-parent FK and a `Workspace`/ancestor FK.

```php
$response = get(route('workspaces.parent-records.children.leaves.show', [
    'workspace' => $leafRecord->childRecord->parentRecord->workspace,
    'parent_record' => $leafRecord->childRecord->parentRecord,
    'child_record' => $leafRecord->childRecord,
    'leaf_record' => $leafRecord,
]));
```

## Coverage Expectations

Route-pattern coverage is complete only when every route parameter has mismatch coverage for the behavior it can trigger. If a child table stores redundant ownership outside the direct parent FK, coverage is incomplete until that inconsistent graph is tested too.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.
- Do not drop applicable route-boundary coverage when adapting examples.
- Do not use real route, module, or entity names in examples.

## Related References

- `references/tests/Feature/Http/Controllers/README.md`
