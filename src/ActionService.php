<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit;

use EscuelaIT\APIKit\Exceptions\ActionModelNotDefinedException;

class ActionService
{
    protected string $actionModel;
    protected array $actionTypes = [];
    protected int $maxModelsPerAction = 100;
    protected string $identifierField = 'id';
    protected array $actionData;
    protected $query;
    protected $user;

    public function hasActionType(string $type): bool
    {
        return isset($this->actionTypes[$type]);
    }

    public function processAction($actionData, $user): ActionResult
    {
        $this->actionData = $actionData;
        $this->user = $user;
        $this->query = $this->createQuery();

        // Avoids querying a huge list of ids that would be rejected anyway
        if (count(array_unique($actionData['relatedIds'])) > $this->maxModelsPerAction) {
            return $this->tooManyModelsError();
        }

        $this->queryModels();

        if ($this->query->count() > $this->maxModelsPerAction) {
            return $this->tooManyModelsError();
        }

        return $this->getActionClass()->processAction();
    }

    public function getMaxModelsPerAction(): int
    {
        return $this->maxModelsPerAction;
    }

    public function queryModels()
    {
        return $this->query->whereIn($this->identifierField, $this->actionData['relatedIds']);
    }

    protected function createQuery()
    {
        if (empty($this->actionModel)) {
            throw new ActionModelNotDefinedException(static::class);
        }

        return $this->actionModel::query();
    }

    protected function tooManyModelsError(): ActionResult
    {
        $message = __('The number of models to process exceeds the maximum allowed (:max).', [
            'max' => $this->maxModelsPerAction,
        ]);

        return ActionResult::error(['relatedIds' => $message], $message);
    }

    private function getModels()
    {
        return $this->query->get();
    }

    private function getActionClass(): CrudAction
    {
        $actionClass = $this->actionTypes[$this->actionData['type']];

        return new $actionClass($this->getModels(), $this->actionData['data'], $this->user);
    }
}
