<?php

declare(strict_types=1);

namespace EscuelaIT\Test\Filters;

use EscuelaIT\APIKit\CustomFilter;
use Illuminate\Database\Eloquent\Builder;

class TitleContainsFilter extends CustomFilter
{
    protected $filterName = 'title_contains';

    public function apply(Builder $query): void
    {
        $value = (string) $this->getFilterValue();
        if ('' !== $value) {
            $query->where('title', 'like', '%'.$value.'%');
        }
    }
}
