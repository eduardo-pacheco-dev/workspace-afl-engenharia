# tests/.pest

## Purpose

This reference defines conventions for Pest shard metadata under `tests/.pest`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `tests/.pest/shards.json` as generated Pest shard metadata, not as a hand-authored behavioral test.

### Rules

- Do not edit shard metadata unless the workflow explicitly requires updating Pest sharding.
- Do not rely on shard metadata as proof that a path has behavioral coverage.
- When adding or moving tests, run the relevant focused test command first; shard updates are separate from proving behavior.

### Generated Shape

```json
{
  "timings": {
    "Tests\\Feature\\Http\\Controllers\\ExampleRecordControllerTest": 1.2345,
    "Tests\\Integration\\Actions\\CreateExampleRecordTest": 0.1234,
    "Tests\\Unit\\Models\\ExampleRecordTest": 0.0456
  },
  "checksum": "example-checksum",
  "updated_at": "2026-01-01T00:00:00+00:00"
}
```

### Review Implication

If CI references a shard failure, inspect the actual failed test file and command output. The shard file only tells how tests are distributed, not why a test failed.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/tests/README.md`
