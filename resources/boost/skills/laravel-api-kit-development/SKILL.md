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

## Quick Start

### Creating a List Service

1. Generate the service class:
```bash
php artisan make:api-list-service YourEntityListService
```

2. Extend `ListService` and configure it:
```php
<?php

namespace App\Services;

use App\Models\YourEntity;
use EscuelaIT\APIKit\ListService;

class YourEntityListService extends ListService
{
    protected string $listModel = YourEntity::class;

    protected ?array $availableFilterColumns = [];

    protected ?array $availableScopes = [];

    protected ?array $availableIncludes = [];

    protected function applyKeywordFilter(?string $keyword): void 
    {
        // Implement your keyword search logic
        $this->query->where('name', 'like', "%{$keyword}%");
    }
}
```

### Creating list Controllers

1. Generate the controller class:
```bash
php artisan make:api-list-controller ListYourEntityController
```

### Creating routes

```
Route::prefix('/YourEntityEndpoints')->group(function() {
    Route::get('/', [ListYourEntityController::class, 'index']);
    Route::get('/ids', [ListYourEntityController::class, 'ids']);
});
```

## Best Practices

- **Keyword Filtering**: Implement `applyKeywordFilter()` in your ListService for user searches
- **Model Scopes**: Use model scopes for complex filtering logic instead of inline queries in the service
- **Filter Columns**: Explicitly list columns that can be filtered to prevent unintended data exposure
