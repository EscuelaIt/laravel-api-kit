# Changelog

## 2.0.0 - Unreleased

### Security

- Fixed a vulnerability in the `include` QueryString parameter. With `$availableIncludes = null`, the previous default, any client could run model and query builder methods by name. For example, `?include=truncate` emptied the table of the listing. Versions before 1.2.3 are affected.
- `setRequiredScope()` no longer lets a request through without applying the scope, which happened when `relationId` was missing or when the scope was not listed in `$availableScopes`. A request without `relationId` now throws `MissingRequiredScopeException`.

### Breaking changes

- With `$requiredScope`, `findIncluding()` now requires `belongsTo` and `relationId` like the other methods, and doesn't find elements outside the scope.
- `ListService` setters now return `static` instead of `ListService`. Subclasses overriding a setter with the `ListService` return type must change it to `static`.
- `ResourceListable::allIds()` now responds 400 instead of 422 to invalid parameters, like `list()` and `findIncluding()`.
- Support for PHP 8.1 and 8.2, Laravel 9, 10 and 11, Laravel 12 before 12.69 and Laravel 13 before 13.30 has been dropped. The package now requires PHP 8.3+ and Laravel 12.69+ or 13.30+, the versions tested in CI and the lowest ones without known security advisories.
- `$availableFilterColumns`, `$availableSortFields`, `$availableScopes` and `$availableIncludes` now default to `[]`, so clients can't filter, sort, apply scopes or include relationships until you list what they can use. These lists also apply to values set with `setSearchConfiguration()` and `setSearchConfigurationValue()`. Custom filters don't depend on them.
- With `$availableIncludes = null`, only methods with a declared relationship return type (e.g. `: HasMany`) and relationships defined with `resolveRelationUsing()` are loaded. Anything else is ignored without being called, including untyped relationships. Relationships listed in `$availableIncludes` don't need a return type.
- `ResourceListable` now responds 400 to an invalid `sortDirection`, and to filters that are not arrays or have no valid `name`.
- Listings are now always sorted by `$identifierField` as the last criterion, unless the query uses `distinct`, `groupBy` or `union`. Listings without `sortField` are therefore sorted by it, and `$identifierField` should be unique.
- `ResourceListable` now responds 400 to `per_page=0`. `ListService` uses a minimum page size of 1, also when `$maxPerPage` is lower.
- `ListService::getAllIds()` no longer calls `getResults()`, so conditions added by overriding `getResults()` no longer apply to it. Use `createQuery()` instead.

### Added

- `$listResource` and `setListResource()`, to apply a Laravel `JsonResource` to the items returned by `list()` and `findIncluding()`.
- `$availableSortFields` and `setAvailableSortFields()`.
- `setAvailableFilterColumns()` and `setIdentifierField()`.
- `lastPage` in paginated listings, next to `countItems`.
- `make:list-service` now generates the `$available*` properties.

### Fixed

- `MissingRequiredScopeException` now gets the documented 403 response instead of a 500.
- Items no longer repeat or get lost between pages because of an unstable order.
- `per_page` is now always an integer in the JSON response, and `per_page=0` no longer skips `$maxPerPage`.
- The count query of paginated listings no longer includes `ORDER BY`, which some databases reject.
- Filters without `active` or `value` no longer cause an error. A filter without `active` is not processed, as documented.
- `ListService::getAllIds()` no longer runs an extra query without limit, and no longer disables pagination on the service instance.

### Upgrading from 1.x

In each `ListService`, list what clients can use:

```php
protected ?array $availableFilterColumns = ['status', 'country'];
protected ?array $availableSortFields = ['name', 'created_at'];
protected ?array $availableScopes = ['byTeam'];
protected ?array $availableIncludes = ['comments', 'author'];
```

Any option not listed is ignored without an error, so the response is still a 200 but without that filter, sort, scope or relationship. Review your frontends and any code calling `setSearchConfiguration()`.

Setting a list to `null` restores the open behavior, and is not recommended.
