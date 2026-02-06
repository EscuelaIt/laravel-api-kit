<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit\Exceptions;

class ActionModelNotDefinedException extends \Exception
{
    public function __construct(string $class = '')
    {
        $message = "The 'actionModel' property is not defined in the {$class} class. "
                 .'You must define the model to use in your ActionService derived class.';

        parent::__construct($message);
    }
}
