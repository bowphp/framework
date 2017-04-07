<?php
namespace Bow\Support\Console;

use Psy\Shell;
use Psy\Configuration;

class Bow
{
    /**
     * @var array
     */
    private $serve_filename = './server.php';

    /**
     * @var array
     */
    private $bootstrap = ['public/index.php'];

    /**
     * @var string
     */
    private $dirname;

    /**
     * @var Command
     */
    private $_command;

    /**
     * Bow constructor.
     * @param string $dirname
     * @param Command $command
     */
    public function __construct($dirname, Command $command)
    {
        if ($command->getParameter('trash')) {
            $this->help();
            exit(1);
        }
        $this->dirname = $dirname;
        $this->_command = $command;
    }

    /**
     * Permet de lancer Bow task runner
     */
    public function run()
    {
        $this->call($this->_command->getParameter('command'));
    }

    /**
     * Permet d'appeler un commande
     *
     * @param string $command
     */
    public function call($command)
    {
        if (! method_exists($this, $command)) {
            $this->help();
            exit(1);
        }

        if (! $this->_command->getParameter('action')) {
            if ($this->_command->getParameter('target') == 'help') {
                $this->help($command);
            }
        }

        try {
            call_user_func_array([$this, $command], [$this->_command->getParameter('target')]);
        } catch (\Exception $e) {
            echo "{$e->getMessage()}"; exit(1);
        }
    }

    /**
     * Permet de lancer un migration
     */
    public function migrate()
    {
        $action = $this->_command->getParameter('action');
        if (! in_array($action, ['up', 'make', 'down'])) {
            throw new \ErrorException('Bad command. Type "php bow migrate help" or "php bow help migrate" for more information"');
        }

        $this->_command->$action($this->_command->getParameter('target'));
    }

    /**
     * Permet de créer des fichiers
     */
    public function create()
    {
        $action = $this->_command->getParameter('action');
        if (! in_array($action, ['middleware', 'controller', 'model'])) {
            $this->help('create');
            exit(0);
        }

        $this->_command->$action($this->_command->getParameter('target'));
    }

    /**
     * Permet de lancer le serveur local
     */
    public function serve()
    {
        $port = (int) $this->_command->options('--port', 5000);
        $hostname = $this->_command->options('--host', 'localhost');
        $settings = $this->_command->getParameter('--php-settings', '');

        if ($settings === true || $settings === null) {
            $settings = '';
        }

        // resource.
        $r = fopen("php://stdout", "w");

        if ($r) {
            fwrite($r, sprintf("[%s] web server start at http://localhost:%s \033[0;31;7mctrl-c for shutdown it\033[00m\n", date('F d Y H:i:s a'), $port));
        }

        fclose($r);
        // lancement du serveur.
        return shell_exec("php -S $hostname:$port server.php $settings");
    }

    /**
     * Permet de lancer le repl
     */
    public function console()
    {
        if ($this->_command->getParameter('target') == 'help') {
            $this->help('console');
            return;
        }

        if (is_string($this->_command->getParameter('--include'))) {
            $this->setBootstrap([$this->_command->getParameter('--include')]);
        }

        if (! class_exists('\Psy\Shell')) {
            echo 'SVP installez psy/psysh:@stable avec la commande "composer require --dev psy/psysh @stable"';
            return;
        }

        $shell = new Shell(new Configuration());
        $shell->setIncludes($this->bootstrap);
        $shell->run();
        return;
    }

    /**
     * Permet de generate un resource sur un controller
     */
    public function generate()
    {
        $action = $this->_command->getParameter('action');
        if (! in_array($action, ['key', 'resource'])) {
            throw new \ErrorException(sprintf(''));
        }

        $this->_command->$action($this->_command->getParameter('target'));
    }

    /**
     * Permet de changer les fichiers de demarage
     *
     * @param array $bootstrap
     */
    public function setBootstrap(array $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Permet de changer les fichiers de demarage
     *
     * @param string $serve_filename
     */
    public function setServerFilename($serve_filename)
    {
        $this->serve_filename = $serve_filename;
    }

    /**
     * Display global help or helper command.
     *
     * @param string|null $command
     * @return int
     */
    private function help($command = null)
    {
        if ($command === null) {
            $usage = <<<USAGE

Bow usage: php bow command:action [name] [help|--with-model|--no-plain|--create|--table|--seed]

\033[0;31mcommand\033[00m:

 \033[0;33mhelp\033[00m display command helper

 \033[0;32mgenerate\033[00m create a new app key and resources
  option:
   \033[0;33mgenerate:resource\033[00m  Create new REST assicate at a controller
   \033[0;33mgenerate:key\033[00m       Create new app key

 \033[0;32mcreate\033[00m                 Create a user class
  option:
   \033[0;33mcreate:middleware\033[00m    Create new middleware
   \033[0;33mcreate:controller\033[00m    Create new controller
   \033[0;33mcreate:model\033[00m         Create new model

 \033[0;32mmigrate\033[00m apply a migration in user model
  option:
   \033[0;33mmigrate:make\033[00m       Create a new migration
   \033[0;33mmigrate:down\033[00m       Drop migration
   \033[0;33mmigrate:up\033[00m         Update or create table of the migration

 \033[0;32mconsole\033[00m show psysh php REPL for debug you code.
 \033[0;32mserver\033[00m run a local web server.

USAGE;
            echo $usage;
            return 0;
        }

        switch($command) {
            case 'help':
                echo "\033[0;33mhelp\033[00m display command helper\n";
                break;
            case 'create':
                echo <<<U
\n\033[0;32mcreate\033[00m create a user class\n
    [option]
    --with-model[=name]  Create a model associte at controller
    --no-plain              Create a plain controller

    * you can use --no-plain --with-model

    \033[0;33m$\033[00m php \033[0;34mbow\033[00m create:controller name [option]  For create a new controlleur
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m create:middleware name           For create a new middleware
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m create:model name                For create a new model
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m create help                      For display this

U;

                break;
            case 'generate':

                echo <<<U
    \n\033[0;32mgenerate\033[00m create a resource and app keyn
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate:resource name             For create a new REST controller
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate:key                       For generate a new APP KEY
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate help                      For display this

U;
                break;
            case 'migrate':
                echo <<<U
\n\033[0;32mmigrate\033[00m apply a migration in user model\n
    [option]
    --seed[--seed=n]      Fill table for n value
    --create=table_name   Change name of table
    --table=table_name    Alter migration table

    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migrate:make name [option]     Create a new migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migrate:up name [option]       Up the specify migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migrate:down name              Down migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migrate [option]               Up all defined migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migrate help                   For display this

U;

                break;

            case 'console':
                echo <<<U
\n\033[0;32mconsole\033[00m show psysh php REPL\n
    php bow console
    >>> //test you code here.
U;
                break;
        }

        exit(0);
    }
}