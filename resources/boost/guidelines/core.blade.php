## Escuela IT Laravel API Kit

escuelait/laravel-api-kit provides a structured, service-based approach to building RESTful APIs with resource listing, filtering, pagination, and response adaptation.

### Core Concepts

**ListService**: An abstract service class that handles querying, filtering, sorting, and pagination for resource listings. Encapsulates business logic away from controllers.

**ResourceListable Trait**: Used in controllers to delegate list operations to a ListService, keeping controllers thin and focused.

### Quick Start

#### Creating a ListService

@verbatim
<code-snippet name="Implement a ListService" lang="php">
<?php

namespace App\Services\ListServices;

use App\Models\EmailList;
use EscuelaIT\APIKit\ListService;

class YourListService extends ListService
{
    protected string $listModel = Your::class;

    protected function applyKeywordFilter(?string $keyword): void 
    {
        $this->query->similar($keyword);
    }
}
</code-snippet>
@endverbatim

#### Using ResourceListable in Controllers

@verbatim
<code-snippet name="Controller with ResourceListable trait" lang="php">
<?php

namespace App\Http\Controllers;

use App\Services\ListServices\YourListService;
use EscuelaIT\APIKit\ResourceListable;

class ListYourController extends Controller
{
    use ResourceListable;

    public function index(YourListService $service)
    {
        return $this->list($service);
    }
}
</code-snippet>
@endverbatim

### Best Practices

- **Keyword Filtering**: Always implement `applyKeywordFilter()` to enable user searches in ListServices
