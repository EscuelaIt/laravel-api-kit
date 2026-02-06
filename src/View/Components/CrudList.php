<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit\View\Components;

use Illuminate\View\Component;

class CrudList extends Component
{
    public array $config;

    public function __construct(
        public string $endpoint,
        public $itemElement = null,
        public $displayProperties = [],
        public ?array $filters = null,
        public ?array $sort = null,
    ) {
        $this->config = [
            'availableFilters' => $this->filters ?? [],
            'sort' => $this->sort ?? null,
            'customization' => [
                'disableFilter' => $this->filters ? false : true,
                'disableSort' => $this->sort ? false : true,
                'disablePagination' => false,
            ],
        ];
    }

    public function render()
    {
        return view('api-kit::components.crud-list');
    }
}
