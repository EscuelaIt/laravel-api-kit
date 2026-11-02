---
status: idea
created: 2026-11-02
---

# Strict parameters

## Problem

Filters, sort fields, scopes and includes that are not allowed are ignored, and the response is still a 200. Nothing warns that an option had no effect. A `belongsTo` whose scope is not listed in `$availableScopes` returns more records than the client expects.

## Proposal

An optional setting that makes `ResourceListable` respond with an error when a request uses a parameter that is not allowed, listing the allowed values.

```php
protected bool $strictParameters = true;
```

## Open questions

- Which status code should it use: 400 or 422?
- Should it be possible to enable it only for some kinds of parameters, such as scopes?
- Should the response list the allowed values? That is useful for developers, but it also shows clients what they can use.
- Should it become the default in a future major version?

## Compatibility

It doesn't break anything if it is disabled by default.
