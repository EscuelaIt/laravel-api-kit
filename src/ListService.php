<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit;

use EscuelaIT\APIKit\Exceptions\ListModelNotDefinedException;
use Illuminate\Support\Str;

class ListService
{
    protected string $listModel;
    protected string $identifierField = 'id';
    protected $query;
    protected bool $paginated = true;
    protected ?array $availableFilterColumns = null;
    protected ?array $availableScopes = null;
    protected ?array $availableIncludes = null;
    protected ?int $maxPerPage = null;
    protected ?int $maxFilters = null;
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

    public function setListModel(string $modelClass): ListService
    {
        $this->listModel = $modelClass;

        return $this;
    }

    public function setPaginated(bool $paginated): ListService
    {
        $this->paginated = $paginated;

        return $this;
    }

    public function setAvailableScopes(?array $scopes): ListService
    {
        $this->availableScopes = $scopes;

        return $this;
    }

    public function setAvailableIncludes(?array $includes): ListService
    {
        $this->availableIncludes = $includes;

        return $this;
    }

    public function setMaxPerPage(?int $maxPerPage): ListService
    {
        $this->maxPerPage = $maxPerPage;

        return $this;
    }

    public function setMaxFilters(?int $maxFilters): ListService
    {
        $this->maxFilters = $maxFilters;

        return $this;
    }

    public function getResults()
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
        if ($this->paginated) {
            return $this->getPaginatedResults();
        }

        return $this->query->get();
    }

    public function findIncluding($identifier)
    {
        $this->query = $this->createQuery();
        $this->normalizeIncludes();
        $this->applyIncludes();

        return $this->query->where($this->identifierField, $identifier)->first();
    }

    public function getAllIds()
    {
        $this->paginated = false;
        $this->getResults();

        return $this->query->get()->pluck($this->identifierField);
    }

    public function setSearchConfiguration(array $config): ListService
    {
        foreach ($config as $key => $value) {
            if (null !== $value) {
                $this->searchConfiguration[$key] = $value;
            }
        }

        return $this;
    }

    public function setSearchConfigurationValue(string $key, mixed $value): ListService
    {
        if (null !== $value) {
            $this->searchConfiguration[$key] = $value;
        }

        return $this;
    }

    protected function createQuery()
    {
        if (empty($this->listModel)) {
            throw new ListModelNotDefinedException(static::class);
        }

        return $this->listModel::query();
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
            if (is_array($item)) {
                foreach ($item as $key => $value) {
                    if (is_string($value) && in_array($value, ['true', 'false'], true)) {
                        $item[$key] = 'true' === $value;
                    }
                }

                return (object) $item;
            }

            return $item;
        }, $filters);

        $this->searchConfiguration['filters'] = $filters;
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
        if (null !== $this->availableScopes) {
            return in_array($scopeName, $this->availableScopes);
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

    private function getPaginatedResults()
    {
        $perPage = $this->searchConfiguration['perPage'];

        if (null !== $this->maxPerPage && $perPage > $this->maxPerPage) {
            $perPage = $this->maxPerPage;
        }

        $countItems = $this->query->count();
        $paginatedResults = $this->query->simplePaginate($perPage)->withQueryString();

        return [
            'countItems' => $countItems,
            'result' => $paginatedResults,
        ];
    }

    private function applyOrder(): void
    {
        if ($this->searchConfiguration['sortField']) {
            $this->query->orderBy($this->searchConfiguration['sortField'], $this->searchConfiguration['sortDirection']);
        }
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
            return $includes;
        }

        return array_values(array_intersect($includes, $this->availableIncludes));
    }

    /**
     * Get the names of all custom filters.
     */
    private function getCustomFilterNames(): array
    {
        return array_map(fn ($filter) => $filter->getFilterName() ?? null, $this->customFilters());
    }
}
