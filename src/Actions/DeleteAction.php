<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit\Actions;

use EscuelaIT\APIKit\ActionResult;
use EscuelaIT\APIKit\CrudAction;
use EscuelaIT\APIKit\Exceptions\UnauthenticatedActionException;

class DeleteAction extends CrudAction
{
    public function handle(): ActionResult
    {
        if (!$this->user) {
            throw new UnauthenticatedActionException();
        }
        $numDeleted = 0;
        $deleteElems = [];
        foreach ($this->models as $model) {
            if ($this->user->can('delete', $model)) {
                $model->delete();
                $deleteElems[] = $model->id;
                ++$numDeleted;
            }
        }
        $message = "Borrados {$numDeleted} ".(1 == $numDeleted ? 'elemento' : 'elementos').' con éxito';

        return $this->createActionResultSuccess($message, [
            'delete_count' => $numDeleted,
            'delete_elems' => $deleteElems,
        ]);
    }
}
