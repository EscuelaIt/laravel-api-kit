<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class MakeListServiceCommand extends GeneratorCommand
{
    protected $name = 'make:api-list-service';
    protected $description = 'Create a new API ListService class';

    protected $type = 'ListService';

    protected function getStub()
    {
        $customPath = base_path('stubs/vendor/api-kit/list-service.stub');

        if (file_exists($customPath)) {
            return $customPath;
        }

        return __DIR__.'/stubs/list-service.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Services';
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the class'],
            ['model', InputArgument::OPTIONAL, 'The model class to use'],
        ];
    }

    protected function buildClass($name)
    {
        $class = parent::buildClass($name);

        $model = $this->argument('model');

        if ($model) {
            // Extraer el nombre del modelo si viene con namespace
            $modelPath = $this->qualifyModel($model);
            $modelBaseName = class_basename($modelPath);

            $class = str_replace(
                ['{{ use_model }}', '{{ model_assignment }}'],
                [
                    "use {$modelPath};\n",
                    ' = '.$modelBaseName.'::class'
                ],
                $class
            );
        } else {
            // Si no se proporciona modelo, remover las líneas innecesarias
            $class = str_replace('{{ use_model }}', '', $class);
            $class = str_replace('{{ model_assignment }}', '', $class);
        }

        return $class;
    }

    protected function qualifyModel(string $model): string
    {
        $model = ltrim($model, '\\/');

        $rootNamespace = $this->laravel->getNamespace();

        if (str_starts_with($model, $rootNamespace)) {
            return $model;
        }

        if (str_contains($model, '\\')) {
            return $model;
        }

        return $rootNamespace.'Models\\'.$model;
    }
}
