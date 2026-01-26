# laravel-api-kit

Utils to create consisten APIs in Laravel.

## Searches in Resource Index

Provides convenient search and filtering features that can be easily implemented in API resources or in any situation where you need to return collections of elements in JSON format.

To implement this utility, two components are required:

- The `ResourceListable` trait in a controller, which provides a `list()` method for generating listings.  
- A listing service, based on `ListService`, for implementing searches on models.

### The ResourceListable Trait

To enable listing functionality, you simply need to implement the `ResourceListable` trait in a controller.

```php
namespace App\Http\Controllers;

use EscuelaIT\APIKit\ResourceListable;

class ListUsersController extends Controller
{
    use ResourceListable;

    public function index()
    {
        return $this->list($userListService);
    }
}
```

By implementing the `ResourceListable` trait in your controller, you get a `list()` method that returns model data in JSON format.

The `ResourceListable` trait is built generically so it can work with any entity you want to expose as a resource. However, to define the model on which searches will be performed, you must pass a service instance to the `list()` method.

In the controller above, that service is called `$userListService`.

### ListService

`ListService` is the base class for services used to configure how listings behave for each resource. To define the behavior of a resource’s listing, you must pass to the `list()` method a ListService, usually extending the `ListService` class provided by this package.

Within `ListService`, you can configure various search and filtering behaviors to be applied in your resource controller. However, for a basic implementation, you only need to specify the model that the listing should use.

To do this, define a `$listModel` property and assign it the model class you want to use for listings.

```php
namespace App\Http\Controllers\User;

use App\Models\User;
use EscuelaIT\APIKit\ListService;

class UserListService extends ListService
{
    protected string $listModel = User::class;
}
```

To make your controller more testable, you can delegate the service instantiation to Laravel’s service container by injecting the instance through the controller’s constructor.

```php
class ListUsersController extends Controller
{
    use ResourceListable;

    public function __construct(
        private UserListService $userListService
    ) {}

    public function index()
    {
        return $this->list($this->userListService);
    }
}
```

### JSON Response in Listings

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

### ListService Configuration via Properties

Inside a resource’s `ListService`, several properties can be configured to customize how listings behave:

- **`$listModel`** — Defines the model class used for the listing.  
- **`$paginated`** — Determines whether the resource results should be paginated. The default value is `true`, meaning pagination is enabled.  
- **`$availableFilterColumns`** — Specifies which columns of the resource are available for filtering. The default value is `null`, allowing filtering by any field. For security reasons, it’s recommended to explicitly restrict this array to only the columns that should be searchable, e.g.:  
  ```php
  protected array $availableFilterColumns = ['is_admin', 'country'];
  ```
- **`$availableScopes`** — Specifies which scopes are allowed to be applied via the `belongsTo` and `relationId` configurations. The default value is `null`, allowing any scope to be applied. For security reasons, it's recommended to explicitly restrict this array to only the scopes that should be allowed, e.g.:  
  ```php
  protected ?array $availableScopes = ['byTeam', 'published'];
  ```
- **`$searchConfiguration`** — Holds an array defining the search configuration for listings. This allows fine-grained customization of multi-parameter searches. The property stores the default configuration but can be overridden using the `setSearchConfiguration()` method, which merges new settings with the existing defaults to adapt searches for specific listings.

### QueryString Configurations for Listing Operations

When working with the `ResourceListable` trait, listing configurations are retrieved from QueryString variables (sent via the URL). These configurations are automatically passed to the `ListService` to customize its behavior. This approach makes it easy to introduce numerous listing customizations that can change with each resource listing request.

**Example of a listing query with QueryString variables:**

```
https://example.com/api/users?sortField=email&sortDirection=desc&per_page=25&keyword=miss&filters[0][name]=is_admin&filters[0][active]=true&filters[0][value]=true
```

#### "keyword" Configuration

Allows sending a keyword to search the model for that keyword.

```
example.com/users?keyword=paul
```

This configuration doesn’t perform any search by itself. You must enable it in the `ListService` by overriding the `createQuery()` method:

```php
protected function createQuery() {
    $keyword = '%' . $this->searchConfiguration['keyword'] . '%';
    return User::where('name', 'like', $keyword)->orWhere('email', 'like', $keyword);
}
```

**Recommendation**: Delegate the search to the model using a scope. Here’s an example using a `similar()` scope (which you’d need to implement with your query logic in the corresponding model):

```php
protected function createQuery() {
    return User::similar($this->searchConfiguration['keyword']);
}
```

#### "sortField" and "sortDirection" Configuration

These two configurations together define the order of search results.

```
example.com/users?sortField=name&sortDirection=desc
```

You can specify any model field to achieve the desired listing order.

Valid `sortDirection` values are `"asc"` (ascending) and `"desc"` (descending).

#### "per_page" Configuration

Allows specifying a custom page size for the resource listing.

```
example.com/users?per_page=25
```

This is only considered if the resource is paginated. To disable pagination, set the `$paginated` boolean property to `false` in `ListService`:

```php
protected bool $paginated = true;
```

If pagination is enabled, the default page size is 10.

#### "filters" Configuration

The `"filters"` configuration allows enabling any number of filters via an array. For each filter, the following data is expected:

- **`active`**: Indicates whether the filter should be applied. If `active` is not received, the filter won’t be processed.
- **`name`**: Column for filtering.
- **`value`**: Value to search for in that column.

For a filter to be processed (with `active: true`), the column must be listed in the `ListService`’s `$availableFilterColumns` array, or that property must be `null` (allowing any column).

> **For enhanced privacy and security, it’s highly recommended to configure the `$availableFilterColumns` array in `ListService`** to prevent users from enabling unintended filters.

**Example**: If the QueryString filters array is:

```json
[
  {
    "name": "is_admin",
    "active": "true",
    "value": "true"
  },
  {
    "name": "country",
    "active": "true",
    "value": "Spain"
  },
  {
    "name": "continent",
    "active": "false",
    "value": "Asia"
  }
]
```

This would filter items where `is_admin = true` **and** `country = Spain`. The `continent` filter would be ignored because `active` is `false`.

#### Custom Filters Implementation

Database column-based filtering is convenient but often insufficient. This package supports custom filters defined by developers.

To implement custom filters, follow these steps:

1. Create a custom filter class.
2. Register custom filters in the `ListService`’s `customFilters()` method.
3. Send the necessary data via QueryString to activate/configure the filter.

Custom filters provide full Eloquent power, allowing complex queries across model columns or related data without restrictions.

##### Creating a Filter Class

First, implement a class defining the custom filter behavior. This class must extend `CustomFilter`:

```php
use EscuelaIT\APIKit\CustomFilter;

class EuropeFilter extends CustomFilter
{
    // ...
}
```

The class must define a `$filterName` property with the filter’s name and an `apply()` method that receives an `Illuminate\Database\Eloquent\Builder` instance:

```php
class EuropeFilter extends CustomFilter
{
    protected $filterName = 'europe';

    public function apply(Builder $query): void
    {
        $query->where(function (Builder $subQuery) {
            $subQuery
                ->where('country', 'Spain')
                ->orWhere('country', 'France');
        });
    }
}
```

The above filter would restrict results to items where `country` is Spain or France.

To access the filter value from QueryString, use `getFilterValue()`:

```php
class TitleContainsFilter extends CustomFilter
{
    protected $filterName = 'title_contains';
    
    public function apply(Builder $query): void
    {
        $value = (string) $this->getFilterValue();
        if ($value !== '') {
            $query->where('title', 'like', '%' . $value . '%');
        }
    }
}
```

**Other available `CustomFilter` methods**:
- `getFilterName()`: Returns the filter name.
- `getFilterData()`: Returns complete filter data from QueryString.
- `isFilterActive()`: Indicates if the filter is active.

##### Registering Filters in customFilters()

In your `ListService`, override the `customFilters()` method to return an array of custom filter instances:

```php
protected function customFilters(): array
{
    return [new TitleContainsFilter()];
}
```

##### Sending Filter Data via QueryString

Include the filter data in the QueryString variables:

```json
[
  {
    "name": "title_contains",
    "active": true,
    "value": "foo"
  }
]
```

- **`name`**: Matches the `$filterName` defined in the filter class.
- **`active`**: Must be `true` to apply the filter (otherwise, `apply()` won’t be called).
- **`value`**: Data passed to the filter for processing.


#### belongsTo and relationId Configurations

Filtering configurations are designed to be dynamically modified by user input, allowing each resource listing request to have highly variable queries.

However, there are cases where you want to **fix certain listing behaviors independently**, without allowing users to modify them via filters. For example, you might want to display a listing showing only invoices for a specific client, where that client cannot be changed via filters. In these cases, you can use **belongsTo** and **relationId** configurations for fixed filtering.

Let’s examine how this property works with an example. Suppose you’re working with a `User` resource model. Users belong to a `Team` model via a `BelongsToMany` relationship:

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

To activate this scope-based filtering via QueryString, send the following configurations:

- **`belongsTo`** variable with value `"byTeam"`
- **`relationId`** variable as an integer

**Example URL:**

```
https://example.com/api/users?belongsTo=byTeam&relationId=2
```

This will activate a scope named `byTeam` on the resource model, passing `2` as the parameter, returning **only users belonging to the team with `id=2`**.

##### Restricting Allowed Scopes with $availableScopes

Similar to `$availableFilterColumns`, you can restrict which scopes are allowed to be applied via the `belongsTo` configuration by setting the `$availableScopes` property.

By default, `$availableScopes` is `null`, which means any scope can be applied via QueryString. For security reasons, it's recommended to explicitly define which scopes are allowed:

```php
protected ?array $availableScopes = ['byTeam', 'published'];
```

With this configuration, only the `byTeam` and `published` scopes can be applied. Any attempt to apply a different scope via QueryString will be ignored.

> **For enhanced security, it's highly recommended to configure the `$availableScopes` array in `ListService`** to prevent users from enabling unintended scopes that might expose sensitive data or cause undesired filtering behavior.
