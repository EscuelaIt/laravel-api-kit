<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit\Exceptions;

class CustomFilterNameNotDefinedException extends \Exception
{
    public function __construct(string $class = '')
    {
        $message = 'The \'filterName\' property is not defined or empty in the '.$class.' custom filter. '
             .'Every CustomFilter must set a non-empty \"filterName\" property (e.g., protected $filterName = \"my_filter\").';

        parent::__construct($message);
    }
}
