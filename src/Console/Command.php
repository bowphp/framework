<?php

namespace Bow\Console;

use Bow\Support\Collection;
use Bow\Support\Str;

class Command
{
    /**
     * @var string
     */
    const BAD_COMMAND = "Please type this command \033[0;32;7m`php bow help` or `php bow command help` for more information.";

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
    private $namespaces = [
        'controller' => 'App\\Controllers',
        'middleware' => 'App\\Middleware',
        'configuration' => 'App\\Configurations',
        'validation' => 'App\\Validations',
        'model' => 'App',
    ];

    /**
     * Command constructor.
     *
     * @param string $dirname
     */
    public function __construct($dirname)
    {
        $this->dirname = rtrim($dirname, '/');

        $this->migration_directory = $this->dirname.'/db/migration';

        $this->seeder_directory = $this->dirname.'/db/seeders';

        $this->controller_directory = $this->dirname.'/app/Controllers';

        $this->middleware_directory = $this->dirname.'/app/Middleware';

        $this->configuration_directory = $this->dirname.'/app/Configurations';

        $this->app_directory = $this->dirname.'/app';

        $this->model_directory = $this->dirname.'/app';

        $this->validation_directory = $this->dirname.'/app/Validations';

        $this->component_directory = $this->dirname.'/components';

        $this->config_directory = $this->dirname.'/config';

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
        return $this->configuration_directory;
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
     * Permet de formater les options
     */
    public function formatParameters()
    {
        foreach ($GLOBALS['argv'] as $key => $param) {
            if ($key == 0) {
                continue;
            }

            if ($key == 1) {
                if (preg_match('/^[a-z]+:[a-z]+$/', $param)) {
                    $part = explode(':', $param);

                    $this->options['command'] = $part[0];

                    $this->options['action'] = $part[1];

                    continue;
                }

                $this->options['command'] = $param;

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
     * Permet de récupérer un parametre
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
     * Permet de récupérer les options de la commande
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
     * Permet de monter une migration
     *
     * @param  string $model
     * @throws mixed
     */
    public function up($model)
    {
        $this->makeMigration($model, 'up');
    }

    /**
     * Permet supprimer une migration dans la base de donnée
     *
     * @param  string $model
     * @throws mixed
     */
    public function down($model)
    {
        $this->makeMigration($model, 'down');
    }

    /**
     * Permet de rafraichir le fichier de régistre
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
     * Permet de créer une migration dans les deux directions
     *
     * @param $model
     * @param $type
     *
     * @throws mixed
     */
    private function makeMigration($model, $type)
    {
        $options = $this->options();

        $param = [];

        if ($options->has('--display-sql') && $options->get('--display-sql') === true) {
            $param = [true];
        }

        if ($type == 'down') {
            if (is_null($model)) {
                if ($options->get('--all') === null) {
                    echo Color::danger(
                        "Cette commande est super dangereuse. Alors veuillez ajout le flag --all pour assurer bow."
                    );

                    exit(1);
                }
            }
        }

        if (!is_null($model)) {
            $model = strtolower($model);

            $fileParten = $this->migration_directory.strtolower("/*{$model}*.php");
        } else {
            $fileParten = $this->migration_directory.strtolower("/*.php");
        }

        $register = ["file" => [], "tables" => []];

        if (!file_exists($this->migration_directory."/.registers")) {
            echo Color::red('Le fichier de régistre de bow est introvable.');

            exit(0);
        }

        $registers = file($this->migration_directory."/.registers");

        if (count($registers) == 0) {
            echo Color::red('Le fichier de régistre de bow est vide (db/migration/.registers');

            exit(0);
        }

        foreach (file($this->migration_directory."/.registers") as $r) {
            $tmp = explode("|", $r);

            $register["file"][] = $tmp[0];

            $register["tables"][] = $tmp[1];
        }

        foreach (glob($fileParten) as $file) {
            if (!file_exists($file)) {
                echo Color::red("$file n'existe pas.");

                exit();
            }

            // Collection des fichiers de migration.
            $filename = preg_replace("@^(".$this->migration_directory."/)|(\.php)$@", "", $file);

            if (in_array($filename, $register["file"])) {
                $num = array_flip($register["file"])[$filename];

                $model = rtrim($register["tables"][$num]);
            }

            @include $file;

            // Formatage de la classe et Exécution de la méthode up ou down
            $class = ucfirst(Str::camel($model));

            if (!class_exists($class)) {
                echo Color::red("Classe \"{$class}\" introuvable. Vérifiez le fichier de régistre (db/migration/.registers).");

                exit(1);
            }

            $instance = new $class;

            call_user_func_array([$instance, strtolower($type)], $param);
        }

        exit(0);
    }

    /**
     * Permet de créer un seeder
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
            echo "\033[0;31mLe seeder exists déja.\033[00m";

            exit(1);
        }

        $options = $this->options();

        $num = (int) $options->get('--n-seed', 5);

        $generator->write('seed', [
            'num' => $num,
            'name' => $name
        ]);

        echo "\033[0;32mLe seeder a bien été créé.\033[00m\n";

        exit(0);
    }

    /**
     * Permet de créer une migration
     *
     * @param  $model
     * @throws \ErrorException
     */
    public function make($model)
    {
        $create_at = date("Y_m_d") . "_" . date("His");

        $generator = new GeneratorCommand(
            $this->migration_directory,
            "${create_at}_${model}"
        );

        $table = "table";

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
            'className' => $class_name
        ]);

        file_put_contents(
            $this->migration_directory."/.registers",
            "${create_at}_${model}|$class_name\n",
            FILE_APPEND
        );

        echo "\033[0;32mLe fichier de migration a été bien créé.\033[00m\n";
    }

    /**
     * Permet de mettre en place le système de réssource.
     *
     * @param  string $controller_name
     * @throws
     */
    public function resource($controller_name)
    {
        $generator = new GeneratorCommand(
            $this->controller_directory,
            $controller_name
        );

        if ($generator->fileExists()) {
            echo Color::danger('Le controlleur existe déja');

            exit(1);
        }

        $prefix = preg_replace("/controller/i", "", strtolower($controller_name));

        $model = ucfirst($prefix);

        $prefix = '/'.trim($prefix, '/');

        $model_namespace = '';

        $options = $this->options();

        if ($options->has('--with-view') && $this->readline("Voulez vous que je crée les vues associées ? ")) {
            $model = preg_replace("/controller/i", "", strtolower($controller_name));

            $model = strtolower($model);

            @mkdir(config('view.path')."/".$model, 0766);

            foreach (["create", "edit", "show", "index"] as $value) {
                $filename = "$model/$value".config('view.extension');

                touch(config('view.path').'/'.$filename);

                echo "$filename added\n";
            }
        }

        $options = $this->options();

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

        if ($this->readline("Voulez vous que je crée un model?")) {
            if ($options->get('--model') === true) {
                echo "\033[0;32;7mLe nom du model non spécifié --model=model_name.\033[00m\n";

                exit(1);
            }

            $model = $options->get('--model');
        }

        $model_namespace = "use App\\".ucfirst($model).";\n";

        $prefix = '/'.strtolower(trim(Str::plurial(Str::snake($model)), '/'));

        $this->createRestController(
            $generator,
            $prefix,
            $controller_name,
            $model_namespace
        );

        $this->model($model);

        if ($this->readline('Voulez vous que je crée une migration pour ce model ? ')) {
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
    private function createRestController(GeneratorCommand $generator, $prefix, $controller_name, $model_namespace = '')
    {
        $generator->write('controller/rest', [
            'modelNamespace' => $model_namespace,
            'prefix' => $prefix,
            'baseNamespace' => $this->namespaces['controller']
        ]);

        echo "\033[0;32mLe controlleur Rest a été bien créé.\033[00m\n";
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
            echo "\033[0;31mLe controlleur existe déjà.\033[00m\n";

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

        echo "\033[0;32mLe controlleur a été bien créé.\033[00m\n";

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
            echo "\033[0;31mLe middleware existe déjà.\033[00m\n";

            exit(1);
        }

        $generator->write('middleware', [
            'baseNamespace' => $this->namespaces['middleware']
        ]);

        echo "\033[0;32mLe middleware a été bien créé.\033[00m\n";

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
            echo "\033[0;33mLe modèle existe déjà.\033[00m\n";

            exit(1);
        }

        $generator->write('model/model', [
            'baseNamespace' => $this->namespaces['model']
        ]);

        echo "\033[0;32mLe modèle a été bien créé.\033[00m\n";

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
     * Permet de créer un validator
     *
     * @param  string $validator_name
     * @return int
     */
    public function validator($validator_name)
    {
        $generator = new GeneratorCommand($this->validation_directory, $validator_name);

        if ($generator->fileExists()) {
            echo "\033[0;33mLe validateur existe déjà.\033[00m\n";

            return 0;
        }

        $generator->write('validator', [
            'baseNamespace' => $this->namespaces['validation']
        ]);

        echo "\033[0;32mLe validateur a été bien créé.\033[00m\n";

        return 0;
    }

    /**
     * Permet de créer un validator
     *
     * @param  string $configuration_name
     * @return int
     */
    public function configuration($configuration_name)
    {
        $generator = new GeneratorCommand($this->configuration_directory, $configuration_name);

        if ($generator->fileExists()) {
            echo "\033[0;33mLa configuration existe déjà.\033[00m\n";

            return 0;
        }

        $generator->write('configuration', [
            'baseNamespace' => $this->namespaces['configuration']
        ]);

        echo "\033[0;32mLa configuration a été bien créé.\033[00m\n";

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
            echo Color::red('Choix invalide')."\n";

            return $this->readline($message);
        }

        if (strtolower($input) == "y") {
            return true;
        }

        return false;
    }
}
