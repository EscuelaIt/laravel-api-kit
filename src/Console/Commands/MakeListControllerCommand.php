<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class MakeListControllerCommand extends GeneratorCommand
{
    protected $name = 'make:api-list-controller';
    protected $description = 'Create a new API List Controller class';

    protected $type = 'Controller';

    protected function getStub()
    {
        $customPath = base_path('stubs/vendor/api-kit/list-controller.stub');

        if (file_exists($customPath)) {
            return $customPath;
        }

        return __DIR__.'/stubs/list-controller.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Controllers';
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the controller'],
            ['service', InputArgument::OPTIONAL, 'The ListService class to use'],
        ];
    }

    protected function buildClass($name)
    {
        $class = parent::buildClass($name);

        $service = $this->argument('service');

        if (!$service) {
            // Generar el nombre del servicio basado en el nombre del controlador
            // ListUsersController -> UserListService
            $service = $this->generateServiceName($name);
        }

        $serviceClass = $this->qualifyService($service);
        $serviceBaseName = class_basename($serviceClass);

        return str_replace(
            ['{{ service_class }}', '{{ service_import }}'],
            [
                $serviceBaseName,
                "use {$serviceClass};",
            ],
            $class
        );
    }

    protected function generateServiceName(string $controllerName): string
    {
        // Remover "Controller" del final si existe
        $name = preg_replace('/Controller$/', '', class_basename($controllerName));

        // Convertir ListUser -> User -> UserListService
        // Asumir que empieza con List
        if (str_starts_with($name, 'List')) {
            $modelName = substr($name, 4);

            return $modelName.'ListService';
        }

        return $name.'ListService';
    }

    protected function qualifyService(string $service): string
    {
        $service = ltrim($service, '\/');

        $rootNamespace = $this->laravel->getNamespace();

        if (str_starts_with($service, $rootNamespace)) {
            return $service;
        }

        // Si ya contiene el prefijo de servicios, retornar como está
        if (str_contains($service, 'Services\\')) {
            return $service;
        }

        // Si contiene backslash pero no Services, es una ruta relativa
        if (str_contains($service, '\\')) {
            return $rootNamespace.'Services\\'.$service;
        }

        return $rootNamespace.'Services\\'.$service;
    }
}
