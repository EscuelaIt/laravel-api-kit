<?php

namespace EscuelaIT\APIKit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Negartarh\APIWrapper\Facades\APIResponse;

trait ResourceListable {

    protected $listValidationRules = [
        'keyword' => 'nullable|string',
        'filters' => 'nullable|array',
        'sortField' => 'nullable|string',
        'sortDirection' => 'nullable|string',
        'per_page' => 'nullable|integer|min:0|max:10000',
        'belongsTo' => 'nullable|string',
        'relationId' => 'nullable|string',
        'include' => 'nullable',
        'include.*' => 'string',
    ];

    public function list(ListService $service) {
        $validator = Validator::make(request()->all(), $this->listValidationRules);
        if($validator->fails()) {
            return APIResponse::unprocessableEntity($validator->errors());
        }

        $results = $service->setSearchConfiguration($this->getSearchConfiguration())->getResults();
        $countItems = isset($results['countItems']) ? $results['countItems'] : count($results);
        return APIResponse::ok($results, $countItems . ' items found');
    }

    private function getSearchConfiguration() {
        return [
            'perPage' => request()->query('per_page'),
            'sortField' => request()->query('sortField'),
            'sortDirection' => request()->query('sortDirection'),
            'keyword' => request()->query('keyword'),
            'filters' => request()->query('filters'),
            'belongsTo' => request()->query('belongsTo'),
            'relationId' => request()->query('relationId'),
            'include' => request()->query('include'),
        ];
    }
}
