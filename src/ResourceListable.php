<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit;

use Illuminate\Support\Facades\Validator;
use Negartarh\APIWrapper\Facades\APIResponse;

trait ResourceListable
{
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

    protected $findIncludingValidationRules = [
        'include' => 'nullable',
        'include.*' => 'string',
    ];

    public function list(ListService $service)
    {
        $validator = $this->getValidator();
        if ($validator->fails()) {
            return APIResponse::badRequest($validator->errors());
        }

        $results = $service->setSearchConfiguration($this->getSearchConfiguration())->getResults();
        $countItems = $results['countItems'] ?? count($results);

        return APIResponse::ok($results, $countItems.' items found');
    }

    public function findIncluding($identifier, ListService $service)
    {
        $validator = $this->getValidatorFindIncluding();
        if ($validator->fails()) {
            return APIResponse::badRequest($validator->errors());
        }
        $element = $service->setSearchConfiguration($this->getSearchConfiguration())->findIncluding($identifier);
        if ($element) {
            return APIResponse::ok($element);
        }

        return APIResponse::notFound();
    }

    public function allIds(ListService $service)
    {
        $validator = $this->getValidator();
        if ($validator->fails()) {
            return APIResponse::unprocessableEntity($validator->errors());
        }

        $allIds = $service->setSearchConfiguration($this->getSearchConfiguration())->getAllIds();

        return APIResponse::ok($allIds);
    }

    private function getValidator()
    {
        return Validator::make(request()->all(), $this->listValidationRules);
    }

    private function getValidatorFindIncluding()
    {
        return Validator::make(request()->all(), $this->findIncludingValidationRules);
    }

    private function getSearchConfiguration()
    {
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
