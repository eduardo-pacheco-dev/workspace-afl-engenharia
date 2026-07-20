# Reference Skeleton

## Purpose

Define the canonical markdown structure for every reference document under `references/**`.

## When To Use

Use this contract when creating or updating any skill reference markdown file.

## Required Pattern

Every markdown file under `references/**` should use these H2 sections in this exact order:

1. `## Purpose`
2. `## When To Use`
3. `## Required Pattern`
4. `## Coverage Expectations`
5. `## Do Not`
6. `## Related References`

Preserve all technical pattern coverage, datasets, and snippets. When an example uses a real module/entity name, convert it to a complete synthetic example instead of deleting it.

References must stay grounded in live repository evidence. When updating a reference, read the files in the matching path plus the closest sibling modules before editing the prose. If the pattern only appears in one area, say that it is a current local pattern instead of turning it into a broad rule.

When a reference touches model integration coverage, link to `references/tests/Integration/Models/README.md` instead of duplicating its full policy text.

## Coverage Expectations

This file defines documentation structure expectations only. Path-specific files define coverage expectations for the project code they map to.

For controller references, coverage expectations must include both action order and nested binding boundaries because those are part of the project contract, not optional examples.

## Do Not

- Do not drop technical coverage during normalization; convert real module/entity examples to synthetic placeholders.
- Do not weaken the model integration policy above.

## Related References

- `SKILL.md`
- `references/tests/Integration/Models/README.md`
- `references/tests/Unit/Models/README.md`
