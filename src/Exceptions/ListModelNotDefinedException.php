<?php

namespace EscuelaIT\APIKit\Exceptions;

use Exception;

class ListModelNotDefinedException extends Exception
{
    public function __construct(string $class = "")
    {
        $message = "The 'listModel' property is not defined in the {$class} class. "
                 . "You must define the model to use in your ListService derived class.";

        parent::__construct($message);
    }
}
