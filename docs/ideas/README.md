# Ideas

Ideas that could be implemented in this package. An idea here is not a commitment.

| Idea | Status |
|---|---|
| [Strict parameters](strict-parameters.md) | idea |
| [Filter, sort and include aliases](filter-sort-aliases.md) | idea |
| [Counts in include](include-counts.md) | idea |
| [Multiple sort fields](multiple-sort-fields.md) | idea |

## How it works

Each idea has its own file, named in kebab-case. Add it to the table above and keep its status up to date:

- `idea`: not decided yet.
- `planned`: it will be implemented.
- `in-progress`: it is being implemented.
- `done`: implemented. The details are in `CHANGELOG.md`.
- `discarded`: it won't be implemented. Keep the file and add the reason, so it doesn't have to be discussed again.

This repository is public. Don't describe unfixed security problems here.

## Template

```markdown
---
status: idea
created: YYYY-MM-DD
---

# Title

## Problem
What is wrong or missing today.

## Proposal
How it could work.

## Open questions
What still has to be decided.

## Compatibility
Whether it breaks anything for current users.
```
