<?php

namespace EscuelaIT\APIKit;

use Illuminate\Support\Str;
use EscuelaIT\APIKit\CustomFilter;
use EscuelaIT\APIKit\Exceptions\ListModelNotDefinedException;

class ListService
{

    protected string $listModel;
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

    protected function createQuery()
    {
        if (empty($this->listModel)) {
            throw new ListModelNotDefinedException(static::class);
        }
        return $this->listModel::query();
    }

    public function getResults()
    {
        $this->query = $this->createQuery();
        $this->normalizeFilters();
        $this->normalizeIncludes();
        $this->applyCustomFilters();
        $this->applySearchFilters();
        $this->applyBelongsTo();
        $this->applyIncludes();
        $this->applyOrder();
        if ($this->paginated) {
            return $this->getPaginatedResults();
        } else {
            return $this->query->get();
        }
    }

    private function getPaginatedResults()
    {
        $perPage = $this->searchConfiguration['perPage'];
        
        if ($this->maxPerPage !== null && $perPage > $this->maxPerPage) {
            $perPage = $this->maxPerPage;
        }
        
        $countItems = $this->query->count();
        $paginatedResults = $this->query->simplePaginate($perPage)->withQueryString();
        return [
            'countItems' => $countItems,
            'result' => $paginatedResults,
        ];
    }

    public function setSearchConfiguration(array $config): ListService
    {
        foreach ($config as $key => $value) {
            if ($value !== null) {
                $this->searchConfiguration[$key] = $value;
            }
        }
        return $this;
    }

    protected function applySearchFilters()
    {
        $filters = $this->removeFiltersNotInAvailableColumns($this->searchConfiguration['filters']);
        $filters = $this->removeCustomFilters($filters);

        // Limit number of filters if maxFilters is set
        if ($this->maxFilters !== null && count($filters) > $this->maxFilters) {
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
        if ($this->availableFilterColumns !== null) {
            $filters = array_filter($filters, function ($filter) {
                return in_array($filter->name, $this->availableFilterColumns);
            });
        }
        return $filters;
    }

    protected function removeCustomFilters(array $filters): array
    {
        $customFilterNames = $this->getCustomFilterNames();

        return array_filter($filters, function ($filter) use ($customFilterNames) {
            return !in_array($filter->name, $customFilterNames);
        });
    }

    /**
     * Normalizes filters to handle both arrays and JSON strings
     * @param mixed $filters
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
                        $item[$key] = $value === 'true';
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

    private function applyOrder()
    {
        if ($this->searchConfiguration['sortField']) {
            $this->query->orderBy($this->searchConfiguration['sortField'], $this->searchConfiguration['sortDirection']);
        }
    }

    private function applyBelongsTo()
    {
        if ($this->searchConfiguration['belongsTo'] != '' && $this->searchConfiguration['relationId'] != '') {
            if ($this->isScopeAllowed($this->searchConfiguration['belongsTo'])) {
                $this->applyScope($this->searchConfiguration['belongsTo'], $this->searchConfiguration['relationId']);
            }
        }
    }

    protected function isScopeAllowed(string $scopeName): bool
    {
        if ($this->availableScopes !== null) {
            return in_array($scopeName, $this->availableScopes);
        }
        return true;
    }

    protected function applyScope($scopeName, $data)
    {
        $method = 'scope'.Str::studly($scopeName);
        if (method_exists($this->listModel, $method)) {
            $this->query->$scopeName($data);
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
        if ($this->availableIncludes === null) {
            return $includes;
        }

        return array_values(array_intersect($includes, $this->availableIncludes));
    }

    protected function customFilters(): array
    {
        return [];
    }

    /**
     * Get the names of all custom filters
     *
     * @return array
     */
    private function getCustomFilterNames(): array
    {
        return array_map(function ($filter) {
            return $filter->getFilterName() ?? null;
        }, $this->customFilters());
    }

    protected function applyCustomFilters()
    {
        foreach ($this->customFilters() as $filter) {
            if (!$filter instanceof CustomFilter) {
                throw new \InvalidArgumentException('Filter must extends CustomFilter class');
            }
            $filter->applyCustomFilter($this->query, $this->searchConfiguration);
        }
    }
}
