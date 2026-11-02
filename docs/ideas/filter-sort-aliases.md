---
status: idea
created: 2026-11-02
---

# Filter, sort and include aliases

## Problem

Clients use the real names of columns and relationships. This exposes internal details, and renaming a column breaks every client that uses it.

## Proposal

Allow a public name that maps to the internal one in the `$available*` lists, keeping plain values as they work today.

```php
protected ?array $availableSortFields = [
    'name',
    'created' => 'created_at',
];
```

## Open questions

- Should responses use the alias too? Field names in the output are already controlled by `$listResource`, so this idea could cover only the query parameters.
- How should it work with `$availableFilterColumns` and custom filters?
- How should it work with nested includes such as `comments.author`?

## Compatibility

It doesn't break anything if lists without keys keep working as they do now.
