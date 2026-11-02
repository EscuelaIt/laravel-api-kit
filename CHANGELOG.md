# Changelog

## 2.0.0 - Unreleased

### Security

- Fixed a vulnerability in the `include` QueryString parameter. With `$availableIncludes = null`, the previous default, any client could run model and query builder methods by name. For example, `?include=truncate` emptied the table of the listing. Versions before 1.2.3 are affected.
- `setRequiredScope()` no longer lets a request through without applying the scope, which happened when `relationId` was missing or when the scope was not listed in `$availableScopes`. A request without `relationId` now throws `MissingRequiredScopeException`.

### Breaking changes

- `$availableFilterColumns`, `$availableSortFields`, `$availableScopes` and `$availableIncludes` now default to `[]`, so clients can't filter, sort, apply scopes or include relationships until you list what they can use. These lists also apply to values set with `setSearchConfiguration()` and `setSearchConfigurationValue()`. Custom filters don't depend on them.
- With `$availableIncludes = null`, only methods with a declared relationship return type (e.g. `: HasMany`) and relationships defined with `resolveRelationUsing()` are loaded. Anything else is ignored without being called, including untyped relationships. Relationships listed in `$availableIncludes` don't need a return type.
- `ResourceListable` now responds 400 to an invalid `sortDirection`, and to filters that are not arrays or have no valid `name`.
- `ListService::getAllIds()` no longer calls `getResults()`, so conditions added by overriding `getResults()` no longer apply to it. Use `createQuery()` instead.

### Added

- `$listResource` and `setListResource()`, to apply a Laravel `JsonResource` to the items returned by `list()` and `findIncluding()`.
- `$availableSortFields` and `setAvailableSortFields()`.
- `setAvailableFilterColumns()`.
- `make:list-service` now generates the `$available*` properties.

### Fixed

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
