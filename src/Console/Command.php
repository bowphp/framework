<?php
namespace Bow\Console;

use Bow\Resource\Storage;
use Bow\Support\Collection;
use Bow\Support\Str;

class Command
{
    /**
     * @var string
     */
    const BAD_COMMAND = "Bad command.%sPlease type this command \033[0;32;7m`php bow help` or `php bow command help` for more information.";

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
    private $service_directory;

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

        $this->service_directory = $this->dirname.'/app/Services';

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
        return $this->service_directory;
    }

    /**
     * Get the service directory
     *
     * @return string
     */
    public function getMiddlewareDirectory()
    {
        return $this->service_directory;
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
     * @param string $model
     * @throws mixed
     */
    public function up($model)
    {
        $this->makeMigration($model, 'up');
    }

    /**
     * Permet supprimer une migration dans la base de donnée
     *
     * @param string $model
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

            file_put_contents($register, basename(str_replace('.php', '', $file))."|".$className."\n", FILE_APPEND);
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
                    echo Color::danger("cette commande est super dangereuse. Alors veuillez ajout le flag --all pour assurer bow.");

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
            echo Color::red('Le fichier de régistre de bow est vide.');

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

            include $file;

            // Formatage de la classe et Exécution de la méthode up ou down
            $class = ucfirst(Str::camel($model));

            if (!class_exists($class)) {
                echo Color::red("Classe \"{$class}\" introvable. Vérifiez vos fichier de régistre.");

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
        $this->filenameIsValide($name);

        $seeder_filename = $this->seeder_directory."/{$name}_seeder.php";

        if (file_exists($seeder_filename)) {
            echo "\033[0;31mLe seeder '$name' exists déja.\033[00m";

            exit(1);
        }

        $options = $this->options();

        $num = 5;

        if ($options->has('--n-seed') && is_int($options->get('--n-seed'))) {
            $num = $options->get('--n-seed', 5);
        }

        $content = <<<SEEDER
<?php

\$seeds = [];

foreach (range(1, $num) as \$key) {
    \$seeds[] = [
        'id' => faker('autoincrement'),
        'created_at' => faker('date'),
        'update_at' => faker('date')
    ];
}

return ['$name' => \$seeds];
SEEDER;
        file_put_contents($seeder_filename, $content);

        echo "\033[0;32mLe seeder \033[00m[$name]\033[0;32m a été bien crée.\033[00m\n";

        exit(0);
    }

    /**
     * Permet de crée une migration
     *
     * @param  $model
     * @throws \ErrorException
     */
    public function make($model)
    {
        $this->filenameIsValide($model);

        $map_method = ["create", "drop"];

        $table = $model;

        $options = $this->options();

        if (file_exists($this->migration_directory."/.registers")) {
            @touch($this->migration_directory."/.registers");
        }

        if ($options->has('--create') && $options->has('--table')) {
            throw new \ErrorException('Bad command.');
        }

        if ($options->has('--table')) {
            if ($options->get('--table') === true) {
                throw new \ErrorException(sprintf(self::BAD_COMMAND, ' [--table] '));
            }

            $table = $options->get('--table');

            $map_method = ["table", "drop"];
        }

        if ($options->has('--create')) {
            if ($options->get('--create') === true) {
                throw new \ErrorException(sprintf(self::BAD_COMMAND, ' [--create] '));
            }

            $table = $options->get('--create');

            $map_method = ["create", "drop"];
        }

        $class_name = ucfirst(Str::camel($model));

        $migrate = <<<doc
<?php

use \Bow\Database\Migration\Schema;
use \Bow\Database\Migration\Migration;
use \Bow\Database\Migration\TablePrinter as Printer;

class {$class_name} extends Migration
{
    /**
     * Create Table
     */
    public function up()
    {
        Schema::{$map_method[0]}("$table", function(Printer \$table) {
            \$table->increment('id');
            \$table->engine('InnoDB');
            \$table->timestamps();
        });
    }

    /**
     * Drop Table
     */
    public function down()
    {
        Schema::{$map_method[1]}("$table");
    }
}
doc;
        $create_at = date("Y_m_d") . "_" . date("His");

        file_put_contents($this->migration_directory."/${create_at}_${model}.php", $migrate);

        Storage::append($this->migration_directory."/.registers", "${create_at}_${model}|$class_name\n");

        echo "\033[0;32mLe file de migration \033[00m[$model]\033[0;32m a été bien crée.\033[00m\n";

        return;
    }

    /**
     * Permet de mettre en place le système de réssource.
     *
     * @param string $controller_name
     * @throws
     */
    public function resource($controller_name)
    {
        $this->filenameIsValide($controller_name);

        $dirname = dirname($controller_name);

        $path = $this->controller_directory."/$controller_name.php";

        if ($dirname != '.') {
            @mkdir($this->controller_directory.'/'.trim($dirname, '/'), 0777, true);

            $namespace = '\\'.str_replace('/', '\\', ucfirst(trim($dirname, '/')));
        } else {
            $namespace = '';
        }

        $prefix = preg_replace("/controller/", "", strtolower($controller_name));

        $prefix = '/'.trim($prefix, '/');

        $filename = $this->controller_directory."/${controller_name}.php";

        if (file_exists($filename)) {
            echo Color::danger('Le controlleur existe déja');

            exit(1);
        }

        $model = ucfirst($path);

        $model_namespace = '';


        if (static::readline("Voulez vous que je crée les vues associées?")) {
            $model = strtolower($model);

            @mkdir($this->component_directory."/views/".$model, 0766);

            echo "\033[0;33;7m";

            foreach (["create", "edit", "show", "index", "update", "delete"] as $value) {
                $file = $this->component_directory."/views/$model/$value.twig";

                echo "$file\n";
            }

            echo "\033[00m";
        }

        $options = $this->options();

        if ($this->readline("Voulez vous que je crée un model?")) {
            if ($options->has('--model')) {
                if ($options->get('--model') !== true) {
                    $model = $options->get('--model');
                } else {
                    echo "\033[0;32;7mLe nom du model non spécifié --model=model_name.\033[00m\n";

                    exit(1);
                }
            }

            $this->model($model);

            $model_namespace = "\nuse App\\".ucfirst($model).';';

            if ($this->readline('Voulez vous que je crée une migration pour ce model? ')) {
                $this->make($model);
            }
        }

        $controllerRestTemplate =<<<CC
<?php

namespace App\Controllers{$namespace};

$model_namespace
use App\Controllers\Controller;

class {$controller_name} extends Controller
{
    /**
     * Point d'entré
     * GET $prefix
     *
     * @return void
     */
    public function index()
    {
        // Codez Ici
    }

    /**
     * Permet d'afficher la vue permettant de créer une résource.
     *
     * GET {$prefix}/create
     * 
     * @return void
     */
    public function create()
    {
        // Codez Ici
    }

    /**
     * Permet d'ajouter une nouvelle résource dans la base d'information
     *
     * POST {$prefix}
     * 
     * @return void
     */
    public function store()
    {
        // Codez Ici
    }

    /**
     * Permet de récupérer un information précise avec un identifiant.
     *
     * GET {$prefix}/:id
     *
     * @param mixed \$id
     * @return void
     */
    public function show(\$id)
    {
        // Codez Ici
    }

    /**
     * Mise à jour d'un ressource en utilisant paramètre du GET
     *
     * GET {$prefix}/:id/edit
     *
     * @param mixed \$id
     * @return void
     */
    public function edit(\$id)
    {
        // Codez Ici
    }

    /**
     * Mise à jour d'une résource
     *
     * PUT {$prefix}/:id
     *
     * @param mixed \$id
     * @return void
     */
    public function update(\$id)
    {
        // Codez Ici
    }

    /**
     * Permet de supprimer une resource
     *
     * DELETE {$prefix}/:id
     *
     * @param mixed \$id
     * @return void
     */
    public function destroy(\$id)
    {
        // Codez Ici
    }
}
CC;
        file_put_contents($this->controller_directory."/${controller_name}.php", $controllerRestTemplate);

        echo "\033[0;32mLe controlleur \033[00m[{$controller_name}]\033[0;32m a été bien crée.\033[00m\n";

        exit(0);
    }

    /**
     * Create new controller file
     *
     * @param string $controller_name
     */
    public function controller($controller_name)
    {
        $this->filenameIsValide($controller_name);

        $dirname = dirname($controller_name);

        $path = $this->controller_directory."/$controller_name.php";

        $classname = basename($controller_name);

        if ($dirname != '.') {
            @mkdir($this->controller_directory.'/'.trim($dirname, '/'), 0777, true);

            $namespace = '\\'.str_replace('/', '\\', ucfirst(trim($dirname, '/')));
        } else {
            $namespace = '';
        }

        if (file_exists($path)) {
            echo "\033[0;31mLe controlleur \033[0;33m\033[0;31m[$controller_name]\033[00m\033[0;31m existe déja.\033[00m\n";

            exit(1);
        }

        if ($this->options('--no-plain')) {
            $content = <<<CONTENT
    
    /**
     * Point d'entré de l'application
     *
     * @return void
     */
    public function index()
    {
        // Codez Ici
    }

    /**
     * Permet d'afficher la vue permettant de créer une résource.
     * 
     * @return void
     */
    public function create()
    {
        // Codez Ici
    }

    /**
     * Permet d 'ajouter une nouvelle résource dans la base d'information
     * 
     * @return void
     */
    public function store()
    {
        // Codez Ici
    }

    /**
     * Permet de récupérer un information précise avec un identifiant.
     *
     * @param mixed \$id L'identifiant de l'élément à récupérer
     * @return void
     */
    public function show(\$id)
    {
        // Codez Ici
    }

    /**
     * Mise à jour d'un ressource en utilisant paramètre du GET
     *
     * @param mixed \$id L'identifiant de l'élément à mettre à jour
     * @return void
     */
    public function edit(\$id)
    {
        // Codez Ici
    }

    /**
     * Mise à jour d'une ressource
     *
     * @param mixed \$id L'identifiant de l'élément à mettre à jour
     * @return void
     */
    public function update(\$id)
    {
        // Codez Ici
    }

    /**
     * Permet de supprimer une ressource
     *
     * @param mixed \$id L'identifiant de l'élément à supprimer
     * @return void
     */
    public function destroy(\$id)
    {
        // Codez Ici
    }
CONTENT;
        } else {
            $content = <<<CONTENT
// Écrivez votre code ici
CONTENT;
        }

        $controller_template =<<<CC
<?php

namespace App\Controllers${namespace};

use App\Controllers\Controller;

class {$classname} extends Controller
{
    $content
}
CC;
        file_put_contents($path, $controller_template);

        echo "\033[0;32mLe controlleur \033[00m\033[1;33m[$controller_name]\033[00m\033[0;32m a été bien crée.\033[00m\n";

        exit(0);
    }

    /**
     * Create a middleware
     *
     * @param $middleware_name
     */
    public function middleware($middleware_name)
    {
        $this->filenameIsValide($middleware_name);

        $path = $this->middleware_directory."/$middleware_name.php";

        if (file_exists($path)) {
            echo "\033[0;31mLe middleware \033[0;33m\033[0;31m[$middleware_name]\033[00m\033[0;31m existe déja.\033[00m\n";

            exit(1);
        }

        $dirname = dirname($middleware_name);

        $classname = ucfirst(basename($middleware_name));

        if ($dirname != '.') {
            @mkdir($this->middleware_directory.'/'.trim($dirname, '/'), 0777, true);

            $namespace = '\\'.str_replace('/', '\\', trim($dirname, '/'));
        } else {
            $namespace = '';
        }

        $middleware_template = <<<CM
<?php

namespace App\Middleware{$namespace};

class {$classname}
{
    /**
     * Fonction de lancement du middleware.
     * 
     * @param \\Bow\\Http\\Request \$request
     * @param callable \$next
     * @return boolean
     */
    public function checker(\$request, callable \$next, \$guard)
    {
        // Codez Ici
        return \$next();
    }
}
CM;
        @mkdir($this->middleware_directory);

        file_put_contents($this->middleware_directory."/$middleware_name.php", $middleware_template);

        echo "\033[0;32mLe middleware \033[00m[{$middleware_name}]\033[0;32m a été bien crée.\033[00m\n";

        exit(0);
    }

    /**
     * Create new model file
     *
     * @param  string      $model_name
     * @param  string|null $table_name
     * @throws
     */
    public function model($model_name, $table_name = null)
    {
        $this->filenameIsValide($model_name);

        $dirname = dirname($model_name);

        if ($dirname != '.') {
            @mkdir($this->model_directory.'/'.trim($dirname, '/'), 0777, true);

            $namespace = '\\'.str_replace('/', '\\', trim($dirname, '/'));
        } else {
            $namespace = '';
        }

        $classname = ucfirst(Str::camel(
            basename($model_name)
        ));

        $model = <<<MODEL
<?php

namespace App${namespace};

use Bow\Database\Barry\Model;

class $classname extends Model
{
    //
}
MODEL;
        if (file_exists($this->model_directory."/${model_name}.php")) {
            echo "\033[0;33mLe model \033[0;33m\033[0;31m[${model_name}]\033[00m\033[0;31m existe déja.\033[00m\n";

            exit(1);
        }

        file_put_contents($this->model_directory."/${model_name}.php", $model);

        echo "\033[0;32mLe model \033[00m[${model_name}]\033[0;32m a été bien crée.\033[00m\n";

        if ($this->options('-m')) {
            $this->make('create_'.strtolower($model_name).'_table');
        }

        exit(0);
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
     * @param  string $name
     * @return int
     */
    public function validation($name)
    {
        $this->filenameIsValide($name);

        if (!is_dir($this->validation_directory)) {
            @mkdir($this->validation_directory);
        }

        $classname = ucfirst(basename($name));

        $namespace = dirname($name);

        if ($namespace == '.') {
            $namespace = '';
        } else {
            @mkdir($this->validation_directory.'/'.$namespace, 0777, true);

            $namespace = '\\'.str_replace('/', '\\', $namespace);
        }

        if (file_exists($this->validation_directory.'/'.$name.'.php')) {
            echo "\033[0;33mLe validateur \033[0;33m\033[0;31m[${name}]\033[00m\033[0;31m existe déja.\033[00m\n";

            return 0;
        }

        $validation = <<<VALIDATOR
<?php

namespace App\Validation{$namespace};

use Bow\Validation\ValidationRequest as Validator;

class {$classname} extends Validator
{
    /**
     * Permet de vérifier la permission d'un utilisateur 
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
    
	/**
	 * Règle de validation
	 * 
	 * @var array
	 */
    protected function rules()
    {
	    return [
            // Vos régles
        ];
    }
        
	/**
	 * Liste des clés à valider
	 * 
	 * @var array
	 */
	protected function keys()
	{
        return [
            '*'
        ];
	}
}
VALIDATOR;

        file_put_contents($this->validation_directory.'/'.$name.'.php', $validation);

        echo "\033[0;32mLe validateur \033[00m[${name}]\033[0;32m a été bien crée.\033[00m\n";

        return 0;
    }

    /**
     * Permet de créer un validator
     *
     * @param  string $name
     * @return int
     */
    public function service($name)
    {
        if (!is_dir($this->service_directory)) {
            @mkdir($this->service_directory);
        }

        if (!preg_match('/service/i', $name)) {
            $name = ucfirst($name).'Service';
        }

        $classname = basename($name);

        $namespace = dirname($name);

        if ($namespace == '.') {
            $namespace = '';
        } else {
            @mkdir($this->service_directory.'/'.$namespace, 0777, true);

            $namespace = '\\'.str_replace('/', '\\', $namespace);
        }

        if (file_exists($this->service_directory.'/'.$name.'.php')) {
            echo "\033[0;33mLe service \033[0;33m\033[0;31m[${name}]\033[00m\033[0;31m existe déja.\033[00m\n";

            return 0;
        }

        $validation = <<<VALIDATOR
<?php

namespace App\Service{$namespace};

use Bow\Config\Config;
use Bow\Application\Services as BowService;

class {$classname} extends BowService
{
    /**
     * Permet de lancement de configuration du service
     * 
     * @param Config \$config
     * @return void
     */
    public function make(Config \$config)
    {
        //
    }

    /**
     * Permet de démarrer le serivce
     * 
     * @return void
     */
    public function start()
    {
        //
    }
}
VALIDATOR;

        file_put_contents($this->service_directory.'/'.$name.'.php', $validation);

        echo "\033[0;32mLe service \033[00m[${name}]\033[0;32m a été bien crée.\033[00m\n";

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

    /**
     * Check if filename is valide
     *
     * @param $filename
     */
    public function filenameIsValide($filename)
    {
        if (is_null($filename)) {
            echo Color::red('Le nom du fichier est invalide.');

            exit(1);
        }
    }
}
