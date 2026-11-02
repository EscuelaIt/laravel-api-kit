---
status: idea
created: 2026-11-02
---

# Counts in include

## Problem

`include` can only load a whole relationship. Showing how many comments a post has requires loading all of them, or adding `withCount()` to `createQuery()`, which runs on every request even if the client doesn't need it.

## Proposal

Let clients request counts of allowed relationships, using `withCount()`.

```
/posts?include=commentsCount
```

```php
protected ?array $availableIncludes = ['commentsCount'];
```

## Open questions

- Should listing `comments` also allow `commentsCount`, or should each one be listed on its own?
- Should it also cover `withExists()`?
- With `$listResource`, the resource has to return the count itself. How should this be documented?

## Compatibility

It doesn't break anything, unless a model has a relationship whose name ends in `Count`.
