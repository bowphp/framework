<?php
namespace Bow\Support\Console;

use function dump;
use Psy\Shell;
use Psy\Configuration;
use Bow\Support\Faker;
use Bow\Database\Database;

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
     * @throws \ErrorException
     */
    public function __construct($dirname, Command $command)
    {
        if ($command->getParameter('trash')) {
            echo Color::red('Bad command. Type "php bow help" for more information"');
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
        if (!method_exists($this, $command)) {
            echo Color::red("Bad $command command .\n");
            exit(1);
        }

        if (!$this->_command->getParameter('action')) {
            if ($this->_command->getParameter('target') == 'help') {
                $this->help($command);
                exit(0);
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
     *
     * @throws \ErrorException
     */
    public function migrate()
    {
        $action = $this->_command->getParameter('action');
        if (!in_array($action, ['up', 'down', null])) {
            throw new \ErrorException('Bad command. Type "php bow help migrate" for more information"');
        }

        if ($action == null) {
            $action = 'up';
            if ($this->_command->getParameter('target') !== null || $this->_command->getParameter('trash') !== null) {
                throw new \ErrorException('Bad command. Type "php bow help migrate" for more information"');
            }
        }

        $target = $this->_command->getParameter('target');

        if ($this->_command->getParameter('target') == '--all') {
            $target = null;
        }

        $this->_command->$action($target);
    }

    /**
     * Permet de créer des fichiers
     *
     * @throws \ErrorException
     */
    public function add()
    {
        $action = $this->_command->getParameter('action');
        if (!in_array($action, ['firewall', 'controller', 'model', 'validation', 'seeder', 'migration', 'service'])) {
            throw new \ErrorException('Bad command. Type "php bow help create" for more information"');
        }

        if ($action == 'migration') {
            $action = 'make';
        }

        $this->_command->$action($this->_command->getParameter('target'));
    }

    /**
     * Permet de lancer le seeding
     */
    public function seed()
    {
        if ($this->_command->getParameter('action') != null) {
            echo "\033[0;32mCommand not found\033[00m\033[00m\n";
            exit(1);
        }

        $options = $this->_command->options();

        if ($options->has('--all')) {
            if ($this->_command->getParameter('target') !== null) {
                echo "\033[0;31mCommand not found\033[00m\033[00m\n";
                exit(1);
            }
        }

        $seeds_filenames = [];

        if ($options->get('--all')) {
            $seeds_filenames = glob($this->dirname.'/seeders/*_seeder.php');
            goto seed;
        }

        if ($this->_command->getParameter('target') !== null) {
            $table_name = $this->_command->getParameter('target');
            if (!is_string($table_name) || !file_exists($this->dirname."/seeders/{$table_name}_seeder.php")) {
                echo "\033[0;32mLe seeder \033[0;33m$table_name\033[00m\033[0;32m n'existe pas.\n";
                exit(1);
            }
            $seeds_filenames = [$this->dirname."/seeders/{$table_name}_seeder.php"];
        }

        seed:
        $seed_collection = [];

        foreach ($seeds_filenames as $filename) {
            $seeds = require $filename;
            Faker::reinitialize();
            $seed_collection = array_merge($seeds, $seed_collection);
        }

        try {
            foreach ($seed_collection as $table => $seeds) {
                $n = Database::table($table)->insert($seeds);
                echo "\033[0;33m'$n' seed".($n > 1 ? 's' : '')." sur la table '$table'\n\033[00m";
            }
        } catch(\Exception $e) {
            echo Color::red($e->getMessage());
            exit(1);
        }

        exit(0);
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

        $doc_root = $this->dirname.'/public';

        // lancement du serveur.
        return shell_exec("php -S $hostname:$port -t $doc_root ".$this->serve_filename." $settings");
    }

    /**
     * Permet de lancer le repl
     */
    public function console()
    {
        if (is_string($this->_command->getParameter('--include'))) {
            $this->setBootstrap(
                array_merge(
                    [$this->bootstrap],
                    [$this->_command->getParameter('--include')]
                )
            );
        }

        if (!class_exists('\Psy\Shell')) {
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
        if (!in_array($action, ['key', 'resource'])) {
            echo Color::red("Bad $action command");
            exit(1);
        }

        $this->_command->$action($this->_command->getParameter('target'));
    }

    /**
     * Permet de supprimer les caches
     *
     * @throws \ErrorException
     */
    public function clear()
    {
        if (in_array($this->_command->getParameter('target'), ['view', 'cache', 'all'])) {
            throw new \ErrorException(sprintf(''));
        }

        $storage = $this->dirname.'/storage';

        if ($this->_command->getParameter('target') == 'cache') {
            $this->unlinks($storage.'/cache/bow');
            return;
        }

        if ($this->_command->getParameter('target') == 'view') {
            $this->unlinks($storage.'/cache/view');
            return;
        }

        $this->unlinks($storage.'/cache/bow');
        $this->unlinks($storage.'/cache/view');
    }

    /**
     * @param string $dirname
     */
    private function unlinks($dirname) {
        $glob = glob($dirname);

        foreach ($glob as $item) {
            @unlink($item);
        }
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

\033[0;32mcommand\033[00m:

 \033[0;33mhelp\033[00m display command helper

 \033[0;32mgenerate\033[00m create a new app key and resources

   \033[0;33mgenerate:resource\033[00m  Create new REST assicate at a controller
   \033[0;33mgenerate:key\033[00m       Create new app key

 \033[0;32madd\033[00m                  Create a user class

   \033[0;33madd:firewall\033[00m      Create new firewall
   \033[0;33madd:service\033[00m       Create new service
   \033[0;33madd:controller\033[00m    Create new controller
   \033[0;33madd:model\033[00m         Create new model
   \033[0;33madd:validator\033[00m     Create new validator
   \033[0;33madd:seeder\033[00m        Create new table fake seeder
   \033[0;33madd:migration\033[00m     Create a new migration

 \033[0;32mmigrate\033[00m apply a migration in user model
  option: [table_name|--all]
   \033[0;33mmigrate:down\033[00m       Drop migration
   \033[0;33mmigrate:up\033[00m         Update or create table of the migration

 \033[0;32mclear\033[00m for clear cache information [not supported]

   \033[0;33mclear:view\033[00m        Clear view cached information
   \033[0;33mclear:cache\033[00m       Clear cache information
   \033[0;33mclear:all\033[00m         Clear all cache information
   
 \033[0;32mseed\033[00m Make seeding
   \033[0;33mseed \033[00m [table_name]    Make seeding for all or one table

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
            case 'add':
                echo <<<U
\n\033[0;32mcreate\033[00m create a user class\n
    [option]
    --with-model[=name]     Create a model associte at controller
    --no-plain              Create a plain controller

    * you can use --no-plain --with-model

    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:controller name [option]  For create a new controlleur
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:firewall name             For create a new firewall
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:service name              For create a new service
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:model name                For create a new model
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:validation name           For create a new validator
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:seeder name [--n-seed=n]  For create a new table seeder
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:migration name            For create a new table migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add help                      For display this

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
    --create=table_name   Change name of table
    --table=table_name    Alter migration table
    --all                 Optionnel

    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migrate:up name [option]       Up the specify migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migrate:down name [--all]      Down migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migrate                        Up all defined migration
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

            case 'clear':
                echo <<<U
\n\033[0;32mclear\033[00m for clear cache information\n

   \033[0;33mclear:view\033[00m        Clear view cached information
   \033[0;33mclear:cache\033[00m       Clear cache information
   \033[0;33mclear:all\033[00m         Clear all cache information
U;
                break;

            case 'seed':
                echo <<<U
\n\033[0;32mseed\033[00m table\n
   option: [name]
   
   \033[0;33mseed \033[00m [option]    Make seeding for all or one table
U;
                break;
        }

        exit(0);
    }
}