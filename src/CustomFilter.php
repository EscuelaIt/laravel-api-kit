<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit;

use EscuelaIT\APIKit\Exceptions\CustomFilterNameNotDefinedException;
use Illuminate\Database\Eloquent\Builder;

abstract class CustomFilter
{
    protected $searchConfiguration;
    protected $filterName;

    public function applyCustomFilter(Builder $query, array $searchConfiguration): void
    {
        if (!is_string($this->filterName) || '' === trim((string) $this->filterName)) {
            throw new CustomFilterNameNotDefinedException(static::class);
        }

        $this->searchConfiguration = $searchConfiguration;
        if ($this->isFilterActive()) {
            $this->apply($query);
        }
    }

    public function getFilterName(): string
    {
        return $this->filterName;
    }

    /**
     * Apply the filter to the query.
     *
     * @param Builder $query The Laravel query builder
     */
    abstract public function apply(Builder $query): void;

    protected function getFilterData()
    {
        $filtered = array_filter(
            $this->searchConfiguration['filters'],
            fn ($filter) => $filter->name === $this->filterName
        );

        return reset($filtered) ?: null;
    }

    protected function isFilterActive(): bool
    {
        $filter = $this->getFilterData();

        return $filter ? $filter->active : false;
    }

    protected function getFilterValue()
    {
        $filter = $this->getFilterData();

        return $filter ? $filter->value : null;
    }
}
