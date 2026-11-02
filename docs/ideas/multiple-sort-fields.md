---
status: idea
created: 2026-11-02
---

# Multiple sort fields

Not needed for now. It was left out when adding `$defaultSortField`.

## Problem

Clients can only sort by one field. Sorting by status and then by date isn't possible, other than with the identifier as the last criterion.

## Proposal

Accept several fields in `sortField`, keeping the current format valid.

```
/posts?sortField[]=status&sortField[]=created_at&sortDirection[]=asc&sortDirection[]=desc
```

## Open questions

- Is this format the best one, or would a single parameter such as `sort=status,-created_at` be better? A new format would have to coexist with the current one.
- Should it support several default fields too?
- Should there be a limit on the number of fields, like `$maxFilters`?

## Compatibility

It doesn't break anything if a single `sortField` keeps working. The validation rules of `ResourceListable` would have to accept arrays.
