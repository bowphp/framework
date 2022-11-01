<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\Color;
use Bow\Console\Generator;
use Bow\Support\Str;

class GenerateResourceControllerCommand extends AbstractCommand
{
    /**
     * Command used to set up the resource system.
     *
     * @param  string $controller
     * @return void
     * @throws
     */
    public function generate(string $controller): void
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
        $model = ucfirst($prefix);
        $prefix = '/'.trim($prefix, '/');

        $model_namespace = '';

        $parameters = $this->arg->getParameters();

        // We check if --with-view exists. If that exists,
        // we launch the question
        if ($parameters->has('--with-view')
            && $this->arg->readline("Do you want me to create the associated views? ")
        ) {
            $model = preg_replace("/controller/i", "", strtolower($controller));

            $model = strtolower($model);

            $this->createDefaultView($model);
        }

        $prefix = Str::plurial(Str::snake($prefix));

        $this->createResourceController(
            $generator,
            $prefix,
            $controller,
            $model_namespace
        );

        exit(0);
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
        $prefix,
        $controller,
        $model_namespace = ''
    ) {
        $generator->write('controller/rest', [
            'modelNamespace' => $model_namespace,
            'prefix' => $prefix,
            'baseNamespace' => $this->namespaces['controller']
        ]);

        echo Color::green('The controller Rest was well created.');
    }

    /**
     * Create the default view for rest Generation
     *
     * @param string $model
     * @return void
     */
    private function createDefaultView($model)
    {
        @mkdir(config('view.path')."/".$model, 0766);

        // We create the default CRUD view
        foreach (["create", "edit", "show", "index"] as $value) {
            $filename = "$model/$value".config('view.extension');

            touch(config('view.path').'/'.$filename);

            echo "$filename added\n";
        }
    }
}
