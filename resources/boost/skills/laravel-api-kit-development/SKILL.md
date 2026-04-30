---
name: laravel-api-kit-development
description: "Use this skill when working with escuelait/laravel-api-kit to build API resources with list services, adapters, and controllers. Covers creating ListServices, implementing ResourceListable trait, customizing ResponseApiAdapters, and building scoped queries for resource filtering."
license: MIT
metadata:
  author: escuelait
  packages: ["escuelait/laravel-api-kit"]
  url: "https://packagist.org/packages/escuelait/laravel-api-kit"
---

# Escuela IT Laravel API Kit Development

escuelait/laravel-api-kit provides a structured way to build RESTful APIs with resource listing, filtering, and pagination capabilities through reusable service classes and traits.

## Core Concepts

### ListService Pattern
The `ListService` is the core abstraction that handles querying, filtering, pagination, and response formatting for resource listings. It decouples business logic from controllers.

### ResourceListable Trait
The `ResourceListable` trait is used in controllers to delegate list operations to a `ListService` instance, keeping controllers thin and focused.

### Action Service
The `ActionService` is the core abstraction for configuring the action execution system, designed to execute actions on resource items.

### Actions
To define particular behaviors for each type of action, we use action classes. Actions must extend the abstract CrudAction class.

### ActionHandler trait
To execute all actions for a resource, we use a single controller that must use the ActionHandler trait, which is responsible for invoking the requested action and passing the required data.

## References

- Resource listing: `./references/api-resource-listing.md` Use this document when you need to perform resource querying and listing operations.
- Configuring action system: `./references/api-action-system-configuration.md` Use this document when you need to configure the action system for a resource.
- Action development: Use this document when you need to develop a specific action.
- Custom filters implementation on searches: `./references/search-custom-filters.md` Use this document when you need to create an arbitrary search filter, beyond the column-value equality filters that are implemented by default in the service.

## Best Practices

- **Keyword Filtering**: Implement `applyKeywordFilter()` in your ListService for user searches
- **Model Scopes**: Use model scopes for complex filtering logic instead of inline queries in the service
- **Filter Columns**: Explicitly list columns that can be filtered to prevent unintended data exposure
