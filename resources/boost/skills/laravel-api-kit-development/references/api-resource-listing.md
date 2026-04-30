# Resource listing

Laravel API Kit provides convenient search and filtering features that can be easily implemented in API resources.

To implement this utility, two components are required:

- A listing service, based on `ListService`, for implementing searches on models.
- Implementing `ResourceListable` trait in a controller, which provides a `list()` and `allIds()` methods for generating listings and retrieving IDs.  

## ListService

`ListService` is the base class for services used to configure how listings behave for each resource. 

Within `ListService`, you can configure various search and filtering behaviors. 

The minimum implementation needs to specify the model that the listing should use, using a `$listModel` property and assign it the model class you want to use for listings.

### ListService Configuration via Properties

Inside a resource’s `ListService`, several properties can be configured to customize how listings behave:

- **`$listModel`** Defines the model class used for the listing.  
- **`$identifierField`** The database table field to identify resource items, default is 'id'.
- **`$paginated`** Determines whether the resource results should be paginated. The default value is `true`, meaning pagination is enabled.  
- **`$maxPerPage`** Sets the maximum page size allowed for paginated results. The default value is `null`, meaning no limit is enforced. 
- **`$availableFilterColumns`** Specifies which columns of the resource are available for filtering. The default value is `null`, allowing filtering by any field. For security reasons, it’s recommended to explicitly restrict this array
- **`$availableScopes`** Specifies which scopes are allowed to be applied via the `belongsTo` and `relationId` configurations. The default value is `null`, allowing any scope to be applied. For security reasons, it's recommended to explicitly restrict this array.
- **`$availableIncludes`** Specifies which relationships are allowed to be included via the `include` QueryString parameter. The default value is `null`, allowing any relationship to be included. For security and performance reasons, it's recommended to explicitly restrict this array to only the relationships that should be loadable.
- **`$searchConfiguration`** Holds an array defining the search configuration for listings. This allows fine-grained customization of multi-parameter searches. The property stores the default configuration but can be overridden using the `setSearchConfiguration()` method, which merges new settings with the existing defaults to adapt searches for specific listings. Usually, it is not necessary to set this array manually, as the ResourceListable trait handles it based on the request made to the controller.
- **`$maxFilters`** Sets the maximum number of filters allowed per query. The default value is `null`, meaning no limit is enforced. When set to a positive integer, any number of active filters exceeding this value will be automatically capped to the configured maximum without raising an error.
- **`$maxIds`** Defines the maximum number of IDs that will be returned when calling the ids() method of the ResourceListable trait, preventing an excessive number of items from being returned that could overload the system. The default value is 100. It can be set to null, in which case there will be no limit.

### ListService configuration via method overriding

- **`applyKeywordFilter(?string $keyword)`** Enable keyword filtering it in the `ListService` by overriding the `applyKeywordFilter()` method.
- **`createQuery()`** Override this method to enforce fixed constraints on listing queries performed by the service. By default, the method returns `$this->listModel::query()`, but it may return a more complex query that, for example, restricts the items a particular user is allowed to see.
- **`customFilters(): array` override the `customFilters()` method to return an array of custom filter instances. To create and configure custom filters use this reference: `./search-custom-filters.md`

### Tipical ListService implementation

```php
<?php

namespace App\Services\ListServices;


use App\Models\InvoiceDraft;
use EscuelaIT\APIKit\ListService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class InvoiceDraftListService extends ListService
{
    protected string $listModel = InvoiceDraft::class;

    protected ?array $availableFilterColumns = [];

    protected ?array $availableScopes = [];

    protected ?array $availableIncludes = [];

    protected function applyKeywordFilter(?string $keyword): void 
    {
        $this->query->similar($keyword); // similar should be a InvoiceDraft scope method
    }

    protected function createQuery()
    {
        $user = Auth::user();
        throw_if(is_null($user), AuthenticationException::class); // only do this if the service should be called by an authenticated user
        
        return $this->listModel::query()->where('company_id', $user->company_id)->with(['customers']);
    }
}
```

## ResourceListable

Implement the `ResourceListable` trait in a controller to return resource listings.

```php
<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Services\ListServices\ProductListService;
use EscuelaIT\APIKit\ResourceListable;

class ListProductController extends Controller
{
    use ResourceListable;

    public function __construct(
        private ProductListService $productListService
    ) {}

    public function index()
    {
        return $this->list($this->productListService);
    }

    public function ids()
    {
        return $this->allIds($this->productListService);
    }
}
```

### Example of a listing query variables sent to a controller

```
https://example.com/api/users?sortField=email&sortDirection=desc&per_page=25&keyword=mike&filters[0][name]=is_admin&filters[0][active]=true&filters[0][value]=true
```

With the default configuration, calling the controller’s method will return a JSON response like the following:

```json
{
  "status": 200,
  "message": "3 items found",
  "data": {
    "countItems": 3,
    "result": {
      "current_page": 1,
      "current_page_url": "http:\/\/localhost\/api\/users?page=1",
      "data": [
        {
          "id": 1,
          "name": "Dr. Richie Considine MD",
          "email": "lizeth21@example.com"
        },
        {
          "id": 2,
          "name": "Prof. Erling Harris",
          "email": "nikita.skiles@example.com"
        },
        {
          "id": 3,
          "name": "Sabrina Lubowitz",
          "email": "tanya54@example.com"
        }
      ],
      "first_page_url": "http:\/\/localhost\/api\/users?page=1",
      "from": 1,
      "next_page_url": null,
      "path": "http:\/\/localhost\/api\/users",
      "per_page": 10,
      "prev_page_url": null,
      "to": 3
    }
  },
  "errors": [],
  "execution": "64ms",
  "version": "1"
}
```

## Supporting advanced `ListService` functionalities.

### belongsTo and relationId Configurations

To **fix certain listing behaviors independently**, without allowing users to modify them via filters. For example, you might want to display a listing showing only invoices for a specific client, where that client cannot be changed via filters. In these cases, you can use **belongsTo** and **relationId** configurations for fixed filtering.

Let’s examine how this property works with an example. Suppose you’re working with a `User` resource model. Users belong to a `Team` model via a `BelongsToMany` relationship. To activate this scope-based filtering the system will recibe a QueryString with:

- **`belongsTo`** variable with value `"byTeam"`
- **`relationId`** variable as an integer

**Example URL:**

```
https://example.com/api/users?belongsTo=byTeam&relationId=2
```

This will activate a scope named `byTeam` on the resource model, passing `2` as the parameter, returning **only users belonging to the team with `id=2`**.

To support this, should exists a model relation:

```php
public function teams(): BelongsToMany
{
    return $this->belongsToMany(Team::class);
}
```

In the `User` model, you can create a scope to filter users by a team identifier:

```php
public function scopeByTeam($query, $teamId) {
    return $query->whereHas('teams', function($query) use ($teamId) {
        $query->where('team_id', $teamId);
    });
}
```

### "include" Configuration

The `"include"` configuration allows eager loading of related entities in listing results. This is useful for including associated data (like comments on posts, or payments for users) without requiring additional queries.

```
example.com/posts?include=comments,author
```

You can specify multiple relationships separated by commas. The data will be eager-loaded using Laravel's `with()` method.

**Example URL with allowed includes:**

```
https://example.com/api/posts?include=comments,author
```

This will return posts with their related comments and authors eager-loaded.