<?php

namespace Bow\Console\Command;

use Bow\Console\Generator;
use Bow\Support\Str;

class ResourceControllerCommand extends AbstractCommand
{
    /**
     * Command used to set up the resource system.
     *
     * @param  string $controller
     *
     * @return void
     * @throws
     */
    public function generate($controller)
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

        $options = $this->arg->options();

        // We check if --with-view exists. If that exists,
        // we launch the question
        if ($options->has('--with-view')
            && $this->readline("Do you want me to create the associated views? ")
        ) {
            $model = preg_replace("/controller/i", "", strtolower($controller));

            $model = strtolower($model);

            $this->createDefaultView($model, $filename);
        }

        // We check if --model flag exists
        // When that not exists we make automaticly filename generation
        if (! $options->has('--model')) {
            $prefix = Str::plurial(Str::snake($prefix));

            $this->createResourceController(
                $generator,
                $prefix,
                $controller,
                $model_namespace
            );

            exit(0);
        }

        // When --model flag exists
        if ($this->arg->readline("Do you want me to create a model?")) {
            if ($options->get('--model') === true) {
                echo "\033[0;32;7mThe name of the unspecified model --model=model_name.\033[00m\n";

                exit(1);
            }

            $model = $options->get('--model');
        }

        // We format the model namespace
        $model_namespace = sprintf("use %s\\$s;\n", $this->namespaces['model'], ucfirst($model));

        $prefix = '/'.strtolower(trim(Str::plurial(Str::snake($model)), '/'));

        $this->createResourceController(
            $generator,
            $prefix,
            $controller,
            $model_namespace
        );

        $this->model($model);

        if ($this->readline('Do you want me to create a migration for this model? ')) {
            $this->make('create_'.strtolower(Str::plurial(Str::snake($model))).'_table');
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
        $prefix,
        $controller,
        $model_namespace = ''
    ) {
        $generator->write('controller/rest', [
            'modelNamespace' => $model_namespace,
            'prefix' => $prefix,
            'baseNamespace' => $this->namespaces['controller']
        ]);

        echo "\033[0;32mThe controller Rest was well created.\033[00m\n";
    }

    /**
     * Create the default view for rest Generation
     *
     * @param string $modal
     * @param string $filename
     *
     * @return void
     */
    private function createDefaultView($model, $filename)
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
