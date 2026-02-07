<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeActionCommand extends GeneratorCommand
{
    protected $name = 'make:api-action'; // o usar $signature
    protected $description = 'Create a new API Action class';

    // Tipo que se muestra en la salida (p.ej. "Action created successfully.")
    protected $type = 'Action';

    protected function getStub()
    {
        $customPath = base_path('stubs/vendor/api-kit/action.stub');

        if (file_exists($customPath)) {
            return $customPath;
        }

        return __DIR__.'/stubs/action.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        // namespace donde quieres que se guarden las clases generadas
        return $rootNamespace.'\Actions';
    }
}
