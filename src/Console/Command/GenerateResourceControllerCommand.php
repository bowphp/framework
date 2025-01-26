<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Console\Generator;
use Bow\Support\Str;
use JetBrains\PhpStorm\NoReturn;

class GenerateResourceControllerCommand extends AbstractCommand
{
    /**
     * Command used to set up the resource system.
     *
     * @param string $controller
     * @return void
     * @throws
     */
    #[NoReturn] public function generate(string $controller): void
    {
        // We create command generator instance
        $generator = new Generator(
            $this->setting->getControllerDirectory(),
            $controller
        );

        // We check if the file already exists
        if ($generator->fileExists()) {
            echo Color::danger('The controller already exists');
            exit(1);
        }

        // We create the resource url prefix
        $prefix = preg_replace("/controller/i", "", strtolower($controller));
        $prefix = '/' . trim($prefix, '/');
        $prefix = Str::plural(Str::snake($prefix));

        $parameters = $this->arg->getParameters();

        $model_namespace = $parameters->has('--model') ? $parameters->get('--model') : '';

        // We check if --with-view exists. If that exists,
        // we launch the question
        if (
            $parameters->has('--with-view')
            && $this->arg->readline("Do you want me to create the associated views? ")
        ) {
            $view_base_directory = preg_replace("/controller/i", "", strtolower($controller));
            $view_base_directory = strtolower($view_base_directory);

            $this->createDefaultView($view_base_directory);
        }

        $this->createResourceController(
            $generator,
            $prefix,
            $controller,
            $model_namespace
        );

        exit(0);
    }

    /**
     * Create the default view for rest Generation
     *
     * @param string $base_directory
     * @return void
     */
    private function createDefaultView(string $base_directory): void
    {
        @mkdir(config('view.path') . "/" . $base_directory, 0766);

        // We create the default CRUD view
        foreach (["create", "edit", "show", "index"] as $value) {
            $filename = "$base_directory/$value" . config('view.extension');

            touch(config('view.path') . '/' . $filename);

            echo "$filename added\n";
        }
    }

    /**
     * Create rest controller
     *
     * @param Generator $generator
     * @param string $prefix
     * @param string $controller
     * @param string $model_namespace
     *
     * @return void
     */
    private function createResourceController(
        Generator $generator,
        string    $prefix,
        string    $controller,
        string    $model_namespace = ''
    ): void
    {
        $generator->write('controller/rest', [
            'modelNamespace' => $model_namespace,
            'prefix' => $prefix,
            'className' => $controller,
            'baseNamespace' => $this->namespaces['controller'] ?? 'App\\Controllers'
        ]);

        echo Color::green('The controller Rest was well created.');
    }
}
