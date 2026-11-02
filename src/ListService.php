<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit;

use EscuelaIT\APIKit\Exceptions\InvalidResourceClassException;
use EscuelaIT\APIKit\Exceptions\ListModelNotDefinedException;
use EscuelaIT\APIKit\Exceptions\MissingRequiredScopeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ListService
{
    protected string $listModel;
    protected ?string $listResource = null;
    protected string $identifierField = 'id';
    protected $query;
    protected bool $paginated = true;
    protected ?array $availableFilterColumns = [];
    protected ?array $availableScopes = [];
    protected ?array $availableIncludes = [];
    protected ?array $availableSortFields = [];
    protected ?int $maxPerPage = null;
    protected ?int $maxFilters = null;
    protected ?int $maxIds = 100;
    protected array $searchConfiguration = [
        'perPage' => 10,
        'sortField' => null,
        'sortDirection' => 'asc',
        'keyword' => null,
        'filters' => [],
        'include' => [],
        'belongsTo' => null,
        'relationId' => null,
    ];
    protected ?string $requiredScope = null;

    public function setListModel(string $modelClass): static
    {
        $this->listModel = $modelClass;

        return $this;
    }

    public function setIdentifierField(string $identifierField): static
    {
        $this->identifierField = $identifierField;

        return $this;
    }

    public function setListResource(?string $resourceClass): static
    {
        $this->listResource = $resourceClass;

        return $this;
    }

    public function setPaginated(bool $paginated): static
    {
        $this->paginated = $paginated;

        return $this;
    }

    public function setAvailableFilterColumns(?array $columns): static
    {
        $this->availableFilterColumns = $columns;

        return $this;
    }

    public function setAvailableScopes(?array $scopes): static
    {
        $this->availableScopes = $scopes;

        return $this;
    }

    public function setAvailableIncludes(?array $includes): static
    {
        $this->availableIncludes = $includes;

        return $this;
    }

    public function setAvailableSortFields(?array $sortFields): static
    {
        $this->availableSortFields = $sortFields;

        return $this;
    }

    public function setMaxPerPage(?int $maxPerPage): static
    {
        $this->maxPerPage = $maxPerPage;

        return $this;
    }

    public function setMaxFilters(?int $maxFilters): static
    {
        $this->maxFilters = $maxFilters;

        return $this;
    }

    public function setMaxIds(?int $maxIds): static
    {
        $this->maxIds = $maxIds;

        return $this;
    }

    public function setRequiredScope(?string $scopeName): static
    {
        $this->requiredScope = $scopeName;

        return $this;
    }

    public function getResults()
    {
        $this->validateRequiredScope();
        $this->validateListResource();
        $this->buildListQuery();
        if ($this->paginated) {
            return $this->getPaginatedResults();
        }

        $results = $this->query->get();

        return $this->listResource ? $results->map(fn ($model) => $this->toResource($model)) : $results;
    }

    public function findIncluding($identifier)
    {
        $this->validateRequiredScope();
        $this->validateListResource();
        $this->query = $this->createQuery();
        $this->normalizeIncludes();
        $this->applyIncludes();
        if (null !== $this->requiredScope) {
            $this->applyBelongsTo();
        }

        $element = $this->query->where($this->identifierField, $identifier)->first();

        return $element && $this->listResource ? $this->toResource($element) : $element;
    }

    public function getAllIds()
    {
        $this->validateRequiredScope();
        $this->buildListQuery();

        $query = $this->query;
        if (null !== $this->maxIds) {
            $query = $query->limit($this->maxIds);
        }

        return $query->get()->pluck($this->identifierField);
    }

    public function setSearchConfiguration(array $config): static
    {
        foreach ($config as $key => $value) {
            if (null !== $value) {
                $this->searchConfiguration[$key] = $value;
            }
        }

        return $this;
    }

    public function setSearchConfigurationValue(string $key, mixed $value): static
    {
        if (null !== $value) {
            $this->searchConfiguration[$key] = $value;
        }

        return $this;
    }

    /**
     * Wraps a plain Collection of models (result of getResults() with paginated=false)
     * into a Resource collection. The Resource collection is returned unresolved,
     * the same way models are returned unserialized today.
     */
    public function wrapCollection(Collection $items, string $resourceClass): AnonymousResourceCollection
    {
        $this->validateResourceClass($resourceClass);

        return $resourceClass::collection($items);
    }

    /**
     * Wraps the paginated array returned by getResults() (['countItems' => ..., 'result' => Paginator])
     * preserving pagination metadata.
     */
    public function wrapPaginated(array $results, string $resourceClass): array
    {
        $this->validateResourceClass($resourceClass);

        $results['result'] = $results['result']->through(
            fn ($item) => new $resourceClass($item)
        );

        return $results;
    }

    /**
     * Wraps a single model (result of findIncluding()). Passes through null untouched.
     *
     * @param mixed $model
     */
    public function wrapModel($model, string $resourceClass)
    {
        if (null === $model) {
            return null;
        }

        $this->validateResourceClass($resourceClass);

        return new $resourceClass($model);
    }

    protected function createQuery()
    {
        if (empty($this->listModel)) {
            throw new ListModelNotDefinedException(static::class);
        }

        return $this->listModel::query();
    }

    protected function validateRequiredScope(): void
    {
        if (null === $this->requiredScope) {
            return;
        }

        // Without relationId the scope would not be applied, see applyBelongsTo()
        if ($this->searchConfiguration['belongsTo'] !== $this->requiredScope || '' == $this->searchConfiguration['relationId']) {
            throw new MissingRequiredScopeException($this->requiredScope);
        }
    }

    protected function applyKeywordFilter(?string $keyword): void
    {
        // Overwrite this method to implement keyword filtering logic
    }

    protected function applySearchFilters(): void
    {
        $filters = $this->removeFiltersNotInAvailableColumns($this->searchConfiguration['filters']);
        $filters = $this->removeCustomFilters($filters);

        // Limit number of filters if maxFilters is set
        if (null !== $this->maxFilters && count($filters) > $this->maxFilters) {
            $filters = array_slice($filters, 0, $this->maxFilters);
        }

        foreach ($filters as $filter) {
            if ($filter->active) {
                $this->query->where($filter->name, $filter->value);
            }
        }
    }

    protected function removeFiltersNotInAvailableColumns(array $filters): array
    {
        if (null !== $this->availableFilterColumns) {
            $filters = array_filter($filters, fn ($filter) => in_array($filter->name, $this->availableFilterColumns));
        }

        return $filters;
    }

    protected function removeCustomFilters(array $filters): array
    {
        $customFilterNames = $this->getCustomFilterNames();

        return array_filter($filters, fn ($filter) => !in_array($filter->name, $customFilterNames));
    }

    /**
     * Normalizes filters to handle both arrays and JSON strings.
     *
     * @return array
     */
    protected function normalizeFilters(): void
    {
        $filters = $this->searchConfiguration['filters'];

        if (empty($filters)) {
            $this->searchConfiguration['filters'] = [];

            return;
        }

        if (is_string($filters)) {
            $filters = json_decode($filters, true);
        }

        if (!is_array($filters)) {
            $this->searchConfiguration['filters'] = [];

            return;
        }

        $filters = array_map(function ($item) {
            if (is_object($item)) {
                $item = (array) $item;
            }

            // Malformed filters are ignored
            if (!is_array($item) || !is_string($item['name'] ?? null)) {
                return null;
            }

            foreach ($item as $key => $value) {
                if (is_string($value) && in_array($value, ['true', 'false'], true)) {
                    $item[$key] = 'true' === $value;
                }
            }

            return (object) ($item + ['active' => false, 'value' => null]);
        }, $filters);

        $this->searchConfiguration['filters'] = array_values(array_filter($filters));
    }

    protected function normalizeIncludes(): void
    {
        $includes = $this->searchConfiguration['include'] ?? [];

        if (empty($includes)) {
            $this->searchConfiguration['include'] = [];

            return;
        }

        if (is_string($includes)) {
            $includes = explode(',', $includes);
        }

        if (!is_array($includes)) {
            $this->searchConfiguration['include'] = [];

            return;
        }

        $includes = array_filter(array_map(function ($item) {
            if (!is_string($item)) {
                return null;
            }

            return trim($item);
        }, $includes));

        $this->searchConfiguration['include'] = array_values(array_unique($includes));
    }

    protected function isScopeAllowed(string $scopeName): bool
    {
        if ($scopeName === $this->requiredScope) {
            return true;
        }

        if (null !== $this->availableScopes) {
            return in_array($scopeName, $this->availableScopes);
        }

        return true;
    }

    protected function isSortFieldAllowed(string $sortField): bool
    {
        if (null !== $this->availableSortFields) {
            return in_array($sortField, $this->availableSortFields);
        }

        return true;
    }

    protected function applyScope($scopeName, $data): void
    {
        $method = 'scope'.Str::studly($scopeName);
        if (method_exists($this->listModel, $method)) {
            $this->query->{$scopeName}($data);
        }
    }

    protected function customFilters(): array
    {
        return [];
    }

    protected function applyCustomFilters(): void
    {
        foreach ($this->customFilters() as $filter) {
            if (!$filter instanceof CustomFilter) {
                throw new \InvalidArgumentException('Filter must extends CustomFilter class');
            }
            $filter->applyCustomFilter($this->query, $this->searchConfiguration);
        }
    }

    private function buildListQuery(): void
    {
        $this->query = $this->createQuery();
        $this->applyKeywordFilter($this->searchConfiguration['keyword']);
        $this->normalizeFilters();
        $this->normalizeIncludes();
        $this->applyCustomFilters();
        $this->applySearchFilters();
        $this->applyBelongsTo();
        $this->applyIncludes();
        $this->applyOrder();
    }

    private function validateListResource(): void
    {
        if (null === $this->listResource) {
            return;
        }

        $this->validateResourceClass($this->listResource);

        if (is_a($this->listResource, ResourceCollection::class, true)) {
            throw new InvalidResourceClassException($this->listResource);
        }
    }

    private function toResource($model): JsonResource
    {
        return new $this->listResource($model);
    }

    private function validateResourceClass(string $resourceClass): void
    {
        if (!is_subclass_of($resourceClass, JsonResource::class)) {
            throw new InvalidResourceClassException($resourceClass);
        }
    }

    private function getPaginatedResults()
    {
        $perPage = (int) $this->searchConfiguration['perPage'];

        if (null !== $this->maxPerPage) {
            $perPage = min($perPage, $this->maxPerPage);
        }

        // simplePaginate() replaces a page size of 0 with the one of the model
        $perPage = max(1, $perPage);

        // Some databases, such as PostgreSQL, reject ORDER BY in a count query
        $countItems = (clone $this->query)->reorder()->count();
        $paginatedResults = $this->query->simplePaginate($perPage)->withQueryString();

        if ($this->listResource) {
            $paginatedResults->through(fn ($model) => $this->toResource($model));
        }

        return [
            'countItems' => $countItems,
            'lastPage' => max(1, (int) ceil($countItems / $perPage)),
            'result' => $paginatedResults,
        ];
    }

    private function applyOrder(): void
    {
        $sortField = $this->searchConfiguration['sortField'];

        if ($sortField && $this->isSortFieldAllowed($sortField)) {
            $this->query->orderBy($sortField, $this->searchConfiguration['sortDirection']);
        }

        // Without a unique last criterion, items can repeat or get lost between pages
        if ($sortField !== $this->identifierField || !$this->isSortFieldAllowed($sortField)) {
            $this->applyIdentifierOrder();
        }
    }

    private function applyIdentifierOrder(): void
    {
        $query = $this->query->toBase();

        // These queries can't be sorted by a column that is not grouped or selected
        if (!empty($query->groups) || !empty($query->unions) || $query->distinct) {
            return;
        }

        $this->query->orderBy($this->query->getModel()->qualifyColumn($this->identifierField));
    }

    private function applyBelongsTo(): void
    {
        if ('' != $this->searchConfiguration['belongsTo'] && '' != $this->searchConfiguration['relationId']) {
            if ($this->isScopeAllowed($this->searchConfiguration['belongsTo'])) {
                $this->applyScope($this->searchConfiguration['belongsTo'], $this->searchConfiguration['relationId']);
            }
        }
    }

    private function applyIncludes(): void
    {
        $includes = $this->filterAllowedIncludes($this->searchConfiguration['include']);
        if (!empty($includes)) {
            $this->query->with($includes);
        }
    }

    private function filterAllowedIncludes(array $includes): array
    {
        if (null === $this->availableIncludes) {
            return array_values(array_filter($includes, fn ($include) => $this->isLoadableRelation($include)));
        }

        return array_values(array_intersect($includes, $this->availableIncludes));
    }

    /**
     * Eloquent calls each eager load name as a method on the model, and models forward
     * unknown methods to the query builder, so "truncate" would empty the table.
     * Names coming from the request must be checked before reaching with().
     */
    private function isLoadableRelation(string $include): bool
    {
        $model = $this->query->getModel();

        foreach (explode('.', Str::before($include, ':')) as $name) {
            if (!$this->isRelationMethod($model, $name)) {
                return false;
            }

            $relation = $model->newInstance()->{$name}();
            if (!$relation instanceof Relation) {
                return false;
            }

            $model = $relation->getRelated();
        }

        return true;
    }

    private function isRelationMethod(Model $model, string $name): bool
    {
        if (!method_exists($model, $name)) {
            // Relations defined with resolveRelationUsing()
            return $model->isRelation($name);
        }

        $method = new \ReflectionMethod($model, $name);
        if (!$method->isPublic() || $method->isStatic() || Model::class === $method->getDeclaringClass()->getName()) {
            return false;
        }

        // Only the declared return type is checked, because finding out what
        // an untyped method returns would require running it.
        $returnType = $method->getReturnType();

        return $returnType instanceof \ReflectionNamedType && is_a($returnType->getName(), Relation::class, true);
    }

    /**
     * Get the names of all custom filters.
     */
    private function getCustomFilterNames(): array
    {
        return array_map(fn ($filter) => $filter->getFilterName() ?? null, $this->customFilters());
    }
}
