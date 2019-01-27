<?php

namespace Bow\Console;

use Bow\Support\Collection;
use Bow\Support\Str;

class Command
{
    /**
     * @var string
     */
    const BAD_COMMAND =
        "Please type this command \033[0;32;7m`php bow help` or `php bow command help` for more information.";

    /**
     * @var string
     */
    private $dirname;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var string
     */
    private $seeder_directory;

    /**
     * @var string
     */
    private $migration_directory;

    /**
     * @var string
     */
    private $controller_directory;

    /**
     * @var string
     */
    private $middleware_directory;

    /**
     * @var string
     */
    private $configuration_directory;

    /**
     * @var string
     */
    private $validation_directory;

    /**
     * @var string
     */
    private $app_directory;

    /**
     * @var string
     */
    private $model_directory;

    /**
     * @var string
     */
    private $component_directory;

    /**
     * @var string
     */
    private $config_directory;

    /**
     * @var array
     */
    private $namespaces = [];

    /**
     * Command constructor.
     *
     * @param string $dirname
     */
    public function __construct($dirname)
    {
        $this->dirname = rtrim($dirname, '/');

        $this->formatParameters();
    }

    /**
     * Set the config directory
     *
     * @param string $dirname
     */
    public function setConfigDirectory($dirname)
    {
        $this->config_directory = $dirname;
    }

    /**
     * Set the package configuration directory
     *
     * @param string $dirname
     */
    public function setConfigurationDirectory($dirname)
    {
        $this->configuration_directory = $dirname;
    }

    /**
     * Set the component directory
     *
     * @param string $dirname
     */
    public function setComponentDirectory($dirname)
    {
        $this->component_directory = $dirname;
    }

    /**
     * Set the migration directory
     *
     * @param string $dirname
     */
    public function setMigrationDirectory($dirname)
    {
        $this->migration_directory = $dirname;
    }

    /**
     * Set the seeder directory
     *
     * @param string $dirname
     */
    public function setSeederDirectory($dirname)
    {
        $this->seeder_directory = $dirname;
    }

    /**
     * Set the controller directory
     *
     * @param string $dirname
     */
    public function setControllerDirectory($dirname)
    {
        $this->controller_directory = $dirname;
    }

    /**
     * Set the validation directory
     *
     * @param string $dirname
     */
    public function setValidationDirectory($dirname)
    {
        $this->validation_directory = $dirname;
    }

    /**
     * Set the middleware directory
     *
     * @param string $dirname
     */
    public function setMiddlewareDirectory($dirname)
    {
        $this->middleware_directory = $dirname;
    }

    /**
     * Set the application directory
     *
     * @param string $dirname
     */
    public function setApplicationDirectory($dirname)
    {
        $this->app_directory = $dirname;
    }

    /**
     * Set the model directory
     *
     * @param string $dirname
     */
    public function setModelDirectory($dirname)
    {
        $this->model_directory = $dirname;
    }

    /**
     * Set the namespaces
     *
     * @param array $namespaces
     */
    public function setNamespaces(array $namespaces)
    {
        foreach ($namespaces as $key => $namespace) {
            $this->namespaces[$key] = $namespace;
        }
    }

    /**
     * Get the component directory
     *
     * @return string
     */
    public function getComponentDirectory()
    {
        return $this->component_directory;
    }

    /**
     * Get the config directory
     *
     * @return string
     */
    public function getConfigDirectory()
    {
        return $this->config_directory;
    }

    /**
     * Get the package configuration directory
     *
     * @return string
     */
    public function getConfigurationDirectory()
    {
        return $this->configuration_directory;
    }

    /**
     * Get the migration directory
     *
     * @return string
     */
    public function getMigrationDirectory()
    {
        return $this->migration_directory;
    }

    /**
     * Get the seeder directory
     *
     * @return string
     */
    public function getSeederDirectory()
    {
        return $this->seeder_directory;
    }

    /**
     * Get the validation directory
     *
     * @return string
     */
    public function getValidationDirectory()
    {
        return $this->migration_directory;
    }

    /**
     * Get the service directory
     *
     * @return string
     */
    public function getServiceDirectory()
    {
        return $this->configuration_directory;
    }

    /**
     * Get the service directory
     *
     * @return string
     */
    public function getMiddlewareDirectory()
    {
        return $this->middleware_directory;
    }

    /**
     * Get the model directory
     *
     * @return string
     */
    public function getModelDirectory()
    {
        return $this->model_directory;
    }

    /**
     * Get the controller directory
     *
     * @return string
     */
    public function getControllerDirectory()
    {
        return $this->model_directory;
    }

    /**
     * Get the app directory
     *
     * @return string
     */
    public function getApplicationDirectory()
    {
        return $this->app_directory;
    }

    /**
     * Get base directory name
     *
     * @return string
     */
    public function getBaseDirname()
    {
        return $this->dirname;
    }

    /**
     * Format the options
     */
    public function formatParameters()
    {
        foreach ($GLOBALS['argv'] as $key => $param) {
            if ($key == 0) {
                continue;
            }

            if ($key == 1) {
                if (!preg_match('/^[a-z]+:[a-z]+$/', $param)) {
                    $this->options['command'] = $param;

                    continue;
                }
                
                $part = explode(':', $param);

                $this->options['command'] = $part[0];

                $this->options['action'] = $part[1];

                continue;
            }

            if ($key == 2) {
                if (preg_match('/^[a-z0-9_\/-]+$/i', $param)) {
                    $this->options['target'] = $param;

                    continue;
                }
            }

            if (preg_match('/^--[a-z-]+$/', $param)) {
                $this->options['options'][$param] = true;

                continue;
            }

            if (count($part = explode('=', $param)) == 2) {
                $this->options['options'][$part[0]] = $part[1];

                continue;
            }

            $this->options['trash'][] = $param;
        }

        if (isset($this->options['options'])) {
            $this->options['options'] = new Collection($this->options['options']);
        }
    }


    /**
     * Retrieves a parameter
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed|Collection|null
     */
    public function getParameter($key, $default = null)
    {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    /**
     * Retrieves the options of the command
     *
     * @param  string $key
     * @param  string $default
     * @return Collection|mixed|null
     */
    public function options($key = null, $default = null)
    {
        $option = $this->getParameter('options', new Collection());

        if ($key == null) {
            return $option;
        }

        return $option->get($key, $default);
    }


    /**
     * Make a migration
     *
     * @param  string $model
     * @throws mixed
     */
    public function up($model)
    {
        $this->makeMigration($model, 'up');
    }

    /**
     * Rollaback migration
     *
     * @param  string $model
     * @throws mixed
     */
    public function down($model)
    {
        $this->makeMigration($model, 'down');
    }

    /**
     * Refresh the log file
     */
    public function reflesh()
    {
        $register = $this->migration_directory.'/.registers';

        file_put_contents($register, '');

        $files = glob($this->migration_directory.'/*.php');

        foreach ($files as $file) {
            $parts = preg_split('/([0-9_])+/', basename($file));

            array_shift($parts);

            $className = '';

            foreach ($parts as $part) {
                $className .= ucfirst(str_replace('.php', '', $part));
            }

            file_put_contents(
                $register,
                basename(str_replace('.php', '', $file))."|".$className."\n",
                FILE_APPEND
            );
        }
    }

    /**
     * Create a migration in both directions
     *
     * @param $model
     * @param $type
     *
     * @throws mixed
     */
    private function makeMigration($model, $type)
    {
        $options = $this->options();

        $fileParten = $this->migration_directory.strtolower("/*.php");

        exit(0);
    }

    /**
     * Create a seeder
     *
     * @param $name
     */
    public function seeder($name)
    {
        $generator = new GeneratorCommand(
            $this->seeder_directory,
            "{$name}_seeder"
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe seeder already exists.\033[00m";

            exit(1);
        }

        $options = $this->options();

        $num = (int) $options->get('--n-seed', 5);

        $generator->write('seed', [
            'num' => $num,
            'name' => $name
        ]);

        echo "\033[0;32mThe seeder has been created.\033[00m\n";

        exit(0);
    }

    /**
     * Create a migration
     *
     * @param  $model
     * @throws \ErrorException
     */
    public function make($model)
    {
        $create_at = date("YmdHis");
        $filename = sprintf("%s%s", ucfirst(Str::camel($model)), $create_at);

        $generator = new GeneratorCommand(
            $this->migration_directory,
            $filename
        );

        $table = "alter";

        $options = $this->options();

        if (file_exists($this->migration_directory."/.registers")) {
            @touch($this->migration_directory."/.registers");
        }

        if ($options->has('--create') && $options->has('--table')) {
            throw new \ErrorException('Bad command.');
        }

        $type = "model/standard";

        if ($options->has('--table')) {
            if ($options->get('--table') === true) {
                throw new \ErrorException(sprintf(self::BAD_COMMAND, ' [--table] '));
            }

            $table = $options->get('--table');

            $type = 'model/table';
        } elseif ($options->has('--create')) {
            if ($options->get('--create') === true) {
                throw new \ErrorException(sprintf(self::BAD_COMMAND, ' [--create] '));
            }

            $table = $options->get('--create');

            $type = 'model/create';
        }

        $class_name = ucfirst(Str::camel($model));

        $generator->write($type, [
            'table' => $table,
            'className' => $filename
        ]);

        table('bow_migration_registers')->insert([
            'migration' => $filename
        ]);

        echo "\033[0;32mThe migration file has been successfully created.\033[00m\n";
    }

    /**
     * Used to set up the resource system.
     *
     * @param  string $controller_name
     * @throws
     */
    public function resource($controller_name)
    {
        // We create command generator instance
        $generator = new GeneratorCommand(
            $this->controller_directory,
            $controller_name
        );

        // We check if the file already exists
        if ($generator->fileExists()) {
            echo Color::danger('The controller already exists');

            exit(1);
        }

        // We create the resource url prefix
        $prefix = preg_replace("/controller/i", "", strtolower($controller_name));
        $model = ucfirst($prefix);
        $prefix = '/'.trim($prefix, '/');

        $model_namespace = '';

        $options = $this->options();

        // We check if --with-view exists. If that exists,
        // we launch the question
        if ($options->has('--with-view')
            && $this->readline("Do you want me to create the associated views? ")
        ) {
            $model = preg_replace("/controller/i", "", strtolower($controller_name));

            $model = strtolower($model);

            @mkdir(config('view.path')."/".$model, 0766);

            // We create the default CRUD view
            foreach (["create", "edit", "show", "index"] as $value) {
                $filename = "$model/$value".config('view.extension');

                touch(config('view.path').'/'.$filename);

                echo "$filename added\n";
            }
        }

        // We check if --model flag exists
        // When that not exists we make automaticly filename generation
        if (! $options->has('--model')) {
            $prefix = Str::plurial(Str::snake($prefix));

            $this->createRestController(
                $generator,
                $prefix,
                $controller_name,
                $model_namespace
            );

            exit(0);
        }

        // When --model flag exists
        if ($this->readline("Do you want me to create a model?")) {
            if ($options->get('--model') === true) {
                echo "\033[0;32;7mThe name of the unspecified model --model=model_name.\033[00m\n";

                exit(1);
            }

            $model = $options->get('--model');
        }

        // We format the model namespace
        $model_namespace = sprintf("use %s\\$s;\n", $this->namespaces['model'], ucfirst($model));

        $prefix = '/'.strtolower(trim(Str::plurial(Str::snake($model)), '/'));

        $this->createRestController(
            $generator,
            $prefix,
            $controller_name,
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
     * @param GeneratorCommand $generator
     * @param $prefix
     * @param $controller_name
     * @param string $model_namespace
     */
    private function createRestController(
        GeneratorCommand $generator,
        $prefix,
        $controller_name,
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
     * Create new controller file
     *
     * @param string $controller_name
     */
    public function controller($controller_name)
    {
        $generator = new GeneratorCommand(
            $this->controller_directory,
            $controller_name
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe controller already exists.\033[00m\n";

            exit(1);
        }

        if ($this->options('--no-plain')) {
            $generator->write('controller/no-plain', [
                'baseNamespace' => $this->namespaces['controller']
            ]);
        } else {
            $generator->write('controller/controller', [
                'baseNamespace' => $this->namespaces['controller']
            ]);
        }

        echo "\033[0;32mThe controller was well created.\033[00m\n";

        exit(0);
    }

    /**
     * Create a middleware
     *
     * @param $middleware_name
     */
    public function middleware($middleware_name)
    {
        $generator = new GeneratorCommand($this->middleware_directory, $middleware_name);

        if ($generator->fileExists()) {
            echo "\033[0;31mThe middleware already exists.\033[00m\n";

            exit(1);
        }

        $generator->write('middleware', [
            'baseNamespace' => $this->namespaces['middleware']
        ]);

        echo "\033[0;32mThe middleware has been well created.\033[00m\n";

        exit(0);
    }

    /**
     * Create new model file
     *
     * @param  string $model_name
     * @throws
     */
    public function model($model_name)
    {
        $generator = new GeneratorCommand($this->model_directory, $model_name);

        if ($generator->fileExists()) {
            echo "\033[0;33mThe model already exists.\033[00m\n";

            exit(1);
        }

        $generator->write('model/model', [
            'baseNamespace' => $this->namespaces['model']
        ]);

        echo "\033[0;32mThe model was well created.\033[00m\n";

        if ($this->options('-m')) {
            $this->make('create_'.strtolower($model_name).'_table');
        }
    }

    /**
     * Permet de générer la clé de securité
     */
    public function key()
    {
        $key = base64_encode(openssl_random_pseudo_bytes(12) . date('Y-m-d H:i:s') . microtime(true));

        file_put_contents($this->config_directory."/.key", $key);

        echo "Application key => \033[0;32m$key\033[00m\n";

        exit;
    }

    /**
     * Create a validator
     *
     * @param  string $validator_name
     * @return int
     */
    public function validator($validator_name)
    {
        $generator = new GeneratorCommand($this->validation_directory, $validator_name);

        if ($generator->fileExists()) {
            echo "\033[0;33mThe validator already exists.\033[00m\n";

            exit(0);
        }

        $generator->write('validator', [
            'baseNamespace' => $this->namespaces['validation']
        ]);

        echo "\033[0;32mThe validator was created well.\033[00m\n";

        exit(0);
    }

    /**
     * Create a configuration
     *
     * @param  string $configuration_name
     * @return int
     */
    public function configuration($configuration_name)
    {
        $generator = new GeneratorCommand($this->configuration_directory, $configuration_name);

        if ($generator->fileExists()) {
            echo "\033[0;33mThe configuration already exists.\033[00m\n";

            return 0;
        }

        $generator->write('configuration', [
            'baseNamespace' => $this->namespaces['configuration']
        ]);

        echo "\033[0;32mThe configuration was well created.\033[00m\n";

        return 0;
    }

    /**
     * Read ligne
     *
     * @param  string $message
     * @return bool
     */
    private function readline($message)
    {
        echo Color::green("$message y/N >>> ");

        $input = strtolower(trim(readline()));

        if (is_null($input) || strlen($input) == 0) {
            $input = 'n';
        }

        if (!in_array($input, ['y', 'n'])) {
            echo Color::red('Invalid choice')."\n";

            return $this->readline($message);
        }

        if (strtolower($input) == "y") {
            return true;
        }

        return false;
    }
}
