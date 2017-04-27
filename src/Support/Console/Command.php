<?php
namespace Bow\Support\Console;


use Bow\Resource\Storage;
use Bow\Support\Collection;
use Bow\Support\Str;

class Command
{
    /**
     * @var string
     */
    const BAD_COMMAND = "Mauvaise commande.%sS'il vous plait tapez la commande \033[0;32;7m`php bow help` ou `php bow command help` pour plus d'information.";

    /**
     * @var string
     */
    private $dirname;

    /**
     * @var array
     */
    private $options = [];

    /**
     * Command constructor.
     * @param string $dirname
     */
    public function __construct($dirname)
    {
        $this->dirname = $dirname;
        $this->formatParameters();
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
                if (preg_match('/^[a-z_-]+$/i', $param)) {
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
     * @param string $key
     * @param mixed $default
     * @return mixed|Collection|null
     */
    public function getParameter($key, $default = null)
    {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    /**
     * Permet de récupérer les options de la commande
     *
     * @param string $key
     * @param string $default
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
     * Permet de monter un migration
     *
     * @param string $model
     */
    public function up($model)
    {
        $this->makeMigration($model, 'up');
    }

    /**
     * Permet supprimer une migration dans la base de donnée
     *
     * @param string $model
     */
    public function down($model)
    {
        $this->makeMigration($model, 'down');
    }

    /**
     * @param $model
     * @param $type
     * @throws \ErrorException
     */
    private function makeMigration($model, $type)
    {
        if ($model) {
            $model = strtolower($model);
            $fileParten = $this->dirname.strtolower("/migration/*{$model}*.php");
        } else {
            $fileParten = $this->dirname.strtolower("/migration/*.php");
        }

        if ($model == null) {
            $type = "up";
        }

        $register = ["file" => [], "tables" => []];

        foreach(file($this->dirname."/migration/.registers") as $r) {
            $tmp = explode("|", $r);
            $register["file"][] = $tmp[0];
            $register["tables"][] = $tmp[1];
        }

        foreach(glob($fileParten) as $file) {
            if (! file_exists($file)) {
                throw new \ErrorException("$file n'existe pas.", E_USER_ERROR);
            }

            // Collection des fichiers de migration.
            $filename = preg_replace("@^(" . $this->dirname."/migration/)|(\.php)$@", "", $file);

            if (in_array($filename, $register["file"])) {
                $num = array_flip($register["file"])[$filename];
                $model = rtrim($register["tables"][$num]);
            }

            require $file;

            // Formatage de la classe et Execution de la methode up ou down
            $class = ucfirst(Str::camel($model));
            $instance = new $class;
            $options = $this->options();
            $param = [];

            if ($options->has('--display-sql') && $options->get('--display-sql') === true) {
                $param = [true];
            }

            call_user_func_array([$instance, strtolower($type)], $param);

            if ($options == null) {
                return;
            }

            if ($options->has('--seed')) {
                $n = (int) $options->get('--seed', 1);
                $r = call_user_func_array([$instance, 'fill'], [$n]);
                $s = $r > 1 ? 's' : '';
                echo "\033[0;33m$r\033[00m \033[0;32mseed$s in \033[00m[$model] \033[0;32mmigration\033[00m\n";
            }
        }
    }

    /**
     * Permet de create une migration
     *
     * @param $model
     * @throws \ErrorException
     */
    public function make($model)
    {
        $mapMethod = ["create", "drop"];
        $table = $model;

        $options = $this->options();

        if ($options->has('--table')) {
            if ($options->get('--table') == true) {
                throw new \ErrorException(sprintf(self::BAD_COMMAND, ' [--table] '));
            }
            $table = $options->get('--table');
            $mapMethod = ["table", "drop"];
        }

        if ($options->has('--create')) {
            if ($options->get('--create') === true) {
                throw new \ErrorException(sprintf(self::BAD_COMMAND, ' [--create] '));
            }
            $table = $options->get('--create');
            $mapMethod = ["create", "drop"];
        }

        $class_name = ucfirst(Str::camel($table));
        $migrate = <<<doc
<?php
use \Bow\Database\Migration\Fields;
use \Bow\Database\Migration\Schema;
use \Bow\Database\Migration\Migration;

class {$class_name} extends Migration
{
    /**
     * create Table
     */
    public function up()
    {
        Schema::{$mapMethod[0]}("$table", function(Fields \$table) {
            \$table->increment('id');
            \$table->timestamps();
        });
    }

    /**
     * Drop Table
     */
    public function down()
    {
        Schema::{$mapMethod[1]}("$table");
    }
}
doc;
        $create_at = date("Y_m_d") . "_" . date("His");
        file_put_contents($this->dirname."/migration/${create_at}_${model}.php", $migrate);
        Storage::append($this->dirname."/migration/.registers", "${create_at}_${model}|$table\n");

        echo "\033[0;32mmigration file \033[00m[$model]\033[0;32m created.\033[00m\n";
        return;
    }

    /**
     * Permet de mettre en place le systeme de resource.
     * @param string $controller_name
     */
    public function resource($controller_name)
    {
        $path = preg_replace("/controller/", "", strtolower($controller_name));
        $model = ucfirst($path);
        $modelNamespace = '';

        if (static::readline("Voulez vous que je crée les vues associées?")) {
            $model = strtolower($model);
            @mkdir($this->dirname."/app/views/".$model, 0766);

            echo "\033[0;33;7m";
            foreach(["create", "edit", "show", "index", "update", "delete"] as $value) {
                $file = $this->dirname."/app/views/$model/$value.twig";
                file_put_contents($file, "{# Vue '$value' du model '$model' #}");
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
                    die();
                }
            }

            $this->model($model);
            $modelNamespace = "\nuse App\\".ucfirst($model).';';

            if ($this->readline('Voulez vous que je crée une migration pour ce model? ')) {
                $this->make($model);
            }
        }

        $controllerRestTemplate =<<<CC
<?php
namespace App\Controllers;
$modelNamespace
use Bow\Database\Database;

class {$controller_name} extends Controller
{
    /**
     * Point d'entré
     * GET /$path
     *
     * @param mixed \$id [optional] L'identifiant de l'element à récupérer
     * @return mixed
     */
    public function index(\$id = null)
    {
        // code ici
    }

    /**
     * Permet d'afficher la vue permettant de créer une résource.
     *
     * GET /$path/create
     */
    public function create()
    {
        // code ici
    }

    /**
     * Permet d'ajouter une nouvelle résource dans la base d'information
     *
     * POST /$path
     */
    public function store()
    {
        // code ici
    }

    /**
     * Permet de récupérer un information précise avec un identifiant.
     *
     * GET /$path/:id
     *
     * @param mixed \$id L'identifiant de l'élément à récupérer
     * @return mixed
     */
    public function show(\$id)
    {
        // code ici
    }

    /**
     * Mise à jour d'un résource en utilisant paramètre du GET
     *
     * GET /$path/:id/edit
     *
     * @param mixed \$id L'identifiant de l'élément à mettre à jour
     * @return mixed
     */
    public function edit(\$id)
    {
        // code ici
    }

    /**
     * Mise à jour d'une résource
     *
     * PUT /$path/:id
     *
     * @param mixed \$id L'identifiant de l'élément à mettre à jour
     * @return mixed
     */
    public function update(\$id)
    {
        // code ici
    }

    /**
     * Permet de supprimer une resource
     *
     * DELETE /$path/:id
     *
     * @param mixed \$id L'identifiant de l'élément à supprimer
     * @return mixed
     */
    public function destroy(\$id)
    {
        // code ici
    }
}
CC;
        file_put_contents($this->dirname."/app/Controllers/${controller_name}.php", $controllerRestTemplate);
        echo "\033[0;32mcontroller created \033[00m[{$controller_name}]\033[0;32m\033[00m\n";
        return;
    }

    /**
     * Create new controller file
     *
     * @param string $controller_name
     */
    public function controller($controller_name)
    {
        if (! preg_match("/controller$/i", $controller_name)) {
            $controller_name = ucfirst($controller_name) . "Controller";
        } else {
            if (preg_match("/^(.+)(controller)$/", $controller_name, $match)) {
                array_shift($match);
                $controller_name = ucfirst($match[0]) . ucfirst($match[1]);
            } else {
                $controller_name = ucfirst($controller_name);
            }
        }

        if (file_exists($this->dirname."/app/Controllers/$controller_name.php")) {
            echo "\033[0;31mcontroller \033[0;33m\033[0;31m[$controller_name]\033[00m\033[0;31m already exist.\033[00m\n";
            return;
        }

        if ($this->options('--no-plain')) {
            $content = <<<CONTENT
    /**
     * Point d'entré de l'application
     *
     * @param mixed \$id [optional] L'identifiant de l'élément à récupérer
     * @return mixed
     */
    public function index(\$id = null)
    {
        // code ici
    }

    /**
     * Permet d'afficher la vue permettant de créer une résource.
     */
    public function create()
    {
        // code ici
    }

    /**
     * Permet d 'ajouter une nouvelle résource dans la base d'information
     */
    public function store()
    {
        // code ici
    }

    /**
     * Permet de récupérer un information précise avec un identifiant.
     *
     * @param mixed \$id L'identifiant de l'élément à récupérer
     * @return mixed
     */
    public function show(\$id)
    {
        // code ici
    }

    /**
     * Mise à jour d'un résource en utilisant paramètre du GET
     *
     * @param mixed \$id L'identifiant de l'élément à mettre à jour
     * @return mixed
     */
    public function edit(\$id)
    {
        // code ici
    }

    /**
     * Mise à jour d'une résource
     *
     * @param mixed \$id L'identifiant de l'élément à mettre à jour
     * @return mixed
     */
    public function update(\$id)
    {
        // code ici
    }

    /**
     * Permet de supprimer une resource
     *
     * @param mixed \$id L'identifiant de l'élément à supprimer
     * @return mixed
     */
    public function destroy(\$id)
    {
        // code ici
    }
CONTENT;
        } else {
            $content = <<<CONTENT
    '// Écrivez votre code ici.';
CONTENT;
        }

        $controller_template =<<<CC
<?php
namespace App\Controllers;

class {$controller_name} extends Controller
{
    $content
}
CC;
        file_put_contents($this->dirname."/app/Controllers/${controller_name}.php", $controller_template);
        echo "\033[0;32mcontroller \033[00m\033[1;33m[$controller_name]\033[00m\033[0;32m created.\033[00m\n";
    }

    /**
     * @param $middleware_name
     * @return int
     */
    public function middleware($middleware_name)
    {
        $middleware_name = ucfirst($middleware_name);

        if (file_exists($this->dirname."/app/Middleware/$middleware_name.php")) {
            echo "\033[0;31mmiddleware \033[0;33m\033[0;31m[$middleware_name]\033[00m\033[0;31m already exist.\033[00m\n";
            return 0;
        }

        $middleware_template = <<<CM
<?php
namespace App\Middleware;

class {$middleware_name}
{
    /**
     * Fonction de lancement du middleware.
     * 
     * @param \\Bow\\Http\\Request \$request
     * @param \\Closure \$next
     * @return boolean
     */
    public function handle(\$request, \\Closure \$next)
    {
        // code ici
        return \$next();
    }
}
CM;
        file_put_contents($this->dirname."/app/Middleware/$middleware_name.php", $middleware_template);
        echo "\033[0;32mmiddleware \033[00m[{$middleware_name}]\033[0;32m created.\033[00m\n";

        return 0;
    }

    /**
     * Create new model file
     *
     * @param string $model_name
     * @param string|null $table_name
     * @return int
     */
    public function model($model_name, $table_name = null)
    {
        $model_name = ucfirst($model_name);
        if (is_string($table_name)) {
            $table_name = strtolower($table_name);
        } else {
            $table_name = strtolower($model_name);
        }

        $model = <<<MODEL
<?php
namespace App;

use Bow\Database\Model;

class ${model_name} extends Model
{
    /**
     * Le nom de la table.
     *
     * @var string
     */
    protected \$table = "$table_name";
}
MODEL;
        if (file_exists($this->dirname."/app/${model_name}.php")) {
            echo "\033[0;33mmodel \033[0;33m\033[0;31m[${model_name}]\033[00m\033[0;31m already exist.\033[00m\n";
            return 0;
        }

        file_put_contents($this->dirname."/app/${model_name}.php", $model);

        echo "\033[0;32mmodel created \033[00m[${model_name}]\033[0;32m\033[00m\n";
        return 0;
    }

    /**
     * Permet de générer la clé de securité
     */
    public function key()
    {
        $key = base64_encode(openssl_random_pseudo_bytes(12) . date('Y-m-d H:i:s') . microtime(true));
        file_put_contents($this->dirname."/config/.key", $key);
        echo "Application key => \033[0;32m$key\033[00m\n";
    }

    /**
     * Read ligne
     *
     * @param string $message
     * @return bool
     */
    private function readline($message)
    {
        echo "\033[0;32m$message y/N\033[00m >>> ";
        $input = readline();

        if (strtolower($input) == "y") {
            return true;
        }

        if (strtolower($input) == 'n' || $input == '') {
            return false;
        }

        return false;
    }
}