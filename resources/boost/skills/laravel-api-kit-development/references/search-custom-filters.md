# Custom filters for searches

The base ListService already implements the ability to perform searches using specific columns and exact values. For example, you can search for users where the is_admin field is true. However, this is not always sufficient.

For example, you might need to perform partial searches, search for multiple accepted values within a column, or perform searches involving multiple columns simultaneously. For these cases, you can implement a custom filter.

To implement custom filters, follow these steps:

1. Create a custom filter class.
2. Register custom filters in the `ListService`’s `customFilters()` method.
3. Send the necessary data via QueryString to activate/configure the filter.

Custom filters provide full Eloquent power, allowing complex queries across model columns or related data without restrictions.

## Creating a Filter Class

First, implement a class defining the custom filter behavior. This class must extend `CustomFilter`:

The class must define a `$filterName` property with the filter’s name and an `apply()` method that receives an `Illuminate\Database\Eloquent\Builder` instance:

```php
class SpanishOrBrazilianFilter extends CustomFilter
{
    protected $filterName = 'hispanic_brazilian';

    public function apply(Builder $query): void
    {
        $query->where(function (Builder $subQuery) {
            $subQuery
                ->where('nationality', 'Spanish')
                ->orWhere('nationality', 'Brazilian');
        });
    }
}
```

The above filter would restrict results to items where `nationality` is Spanish or Brazilian.

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

## Registering Filters in customFilters()

In your `ListService`, override the `customFilters()` method to return an array of custom filter instances:

```php
protected function customFilters(): array
{
    return [new TitleContainsFilter()];
}
```

## Sending Filter Data via QueryString

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