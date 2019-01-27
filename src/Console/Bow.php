<?php

namespace Bow\Console;

use Bow\Configuration\Loader;
use Bow\Database\Database;
use Bow\Support\Faker;

class Bow
{
    /**
     * The server file
     *
     * @var string
     */
    private $serve_filename;

    /**
     * The bootstrap files
     *
     * @var array
     */
    private $bootstrap = [];

    /**
     * The start directory
     *
     * @var string
     */
    private $dirname;

    /**
     * The COMMAND instance
     *
     * @var Command
     */
    private $command;

    /**
     * The the public directory
     *
     * @var string
     */
    private $public_directory;

    /**
     * The the storage directory
     *
     * @var string
     */
    private $var_directory;

    /**
     * The Loader instance
     *
     * @var Loader
     */
    private $kernel;

    /**
     * The custom command registers
     *
     * @var array
     */
    private $registers;

    /**
     * The command list
     *
     * @var array
     */
    const COMMAND = ['add', 'migrate', 'run', 'generate', 'seed', 'help'];

    /**
     * The action list
     *
     * @var array
     */
    const ACTION = [
        'middleware', 'controller', 'model', 'validator',
        'seeder', 'migration', 'configuration'
    ];

    /**
     * Bow constructor.
     *
     * @param  Command $command
     * @return void
     */
    public function __construct(Command $command)
    {
        if ($command->getParameter('trash')) {
            $this->throwFailsCommand('help');
        }

        $this->dirname = $command->getBaseDirname();

        $this->command = $command;
    }

    /**
     * Set public directory
     *
     * @param string $dirname
     */
    public function setPublicDirectory($dirname)
    {
        $this->public_directory = $dirname;
    }

    /**
     * Set var directory
     *
     * @param string $dirname
     */
    public function setVarDirectory($dirname)
    {
        $this->var_directory = $dirname;
    }

    /**
     * Set the bootstrap files
     *
     * @param  array $bootstrap
     * @return void
     */
    public function setBootstrap(array $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Set the server file
     *
     * @param string $serve_filename
     * @return void
     */
    public function setServerFilename($serve_filename)
    {
        $this->serve_filename = $serve_filename;
    }

    /**
     * Bind kernel
     *
     * @param Loader $kernel
     */
    public function bind(Loader $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Launch Bow task runner
     *
     * @return void
     * @throws
     */
    public function run()
    {
        foreach ($this->bootstrap as $item) {
            require $item;
        }
        
        $command = $this->command->getParameter('command');

        if (array_key_exists($command, $this->registers)) {
            return $this->registers[$command]();
        }

        if ($command == 'launch') {
            $command = null;
        }

        if ($command == 'run') {
            $command = 'launch';
        }

        $this->kernel->boot();

        $this->call($command);
    }

    /**
     * Calls a command
     *
     * @param  string $command
     * @return void
     * @throws
     */
    public function call($command)
    {
        if (!in_array($command, static::COMMAND)) {
            echo Color::red("The command '$command' not exists !\n");

            $this->throwFailsCommand('help');
        }

        if (!$this->command->getParameter('action')) {
            if ($this->command->getParameter('target') == 'help') {
                $this->help($command);

                exit(0);
            }
        }

        try {
            call_user_func_array(
                [$this, $command],
                [$this->command->getParameter('target')]
            );
        } catch (\Exception $e) {
            echo $e->getMessage();

            exit(1);
        }
    }

    /**
     * Add a custom order to the store
     *
     * @param string $command
     * @param callable $cb
     * @return Bow
     */
    public function addCommand($command, $cb)
    {
        $this->registers[$command] = $cb;
    }

    /**
     * Launch a migration
     *
     * @return void
     *
     * @throws \ErrorException
     */
    public function migrate()
    {
        $action = $this->command->getParameter('action');

        if (!in_array($action, ['up', 'down', 'refresh', null])) {
            echo Color::red("This action is not exists!");

            $this->throwFailsCommand('help migrate');
        }

        if ($action == null) {
            $action = 'up';

            if ($this->command->getParameter('target') !== null
                || $this->command->getParameter('trash') !== null
            ) {
                $this->throwFailsCommand('help migrate');
            }
        }

        $target = $this->command->getParameter('target');

        if ($this->command->getParameter('target') == '--all') {
            $target = null;
        }

        $this->command->$action($target);
    }

    /**
     * Create files
     *
     * @return void
     *
     * @throws \ErrorException
     */
    public function add()
    {
        $action = $this->command->getParameter('action');

        if (!in_array($action, static::ACTION)) {
            echo Color::red("This action is not exists");

            $this->throwFailsCommand('help create');
        }

        if ($action == 'migration') {
            $action = 'make';
        }

        $this->command->$action(
            $this->command->getParameter('target')
        );
    }

    /**
     * Launch seeding
     *
     * @return void
     * @throws
     */
    public function seed()
    {
        $action = $this->command->getParameter('action');

        if (!in_array($action, ['table', 'all'])) {
            echo Color::red("This action is not exists");

            $this->throwFailsCommand('help seed');
        }

        if ($action == 'all') {
            if ($this->command->getParameter('target') != null) {
                echo Color::red("Bad command");

                $this->throwFailsCommand('help seed');
            }
        }

        $seeds_filenames = [];

        if ($action == 'all') {
            $seeds_filenames = glob($this->command->getSeederDirectory().'/*_seeder.php');
        } elseif ($action == 'table') {
            $table_name = trim($this->command->getParameter('target', null));

            if (is_null($table_name)) {
                echo Color::red('Specify the seeder table name');

                $this->throwFailsCommand('help seed');
            }

            if (!file_exists($this->command->getSeederDirectory()."/{$table_name}_seeder.php")) {
                echo Color::red("Seeder $table_name not exists.");

                exit(1);
            }

            $seeds_filenames = [
                $this->command->getSeederDirectory()."/{$table_name}_seeder.php"
            ];
        }

        $seed_collection = [];

        $faker = \Faker\Factory::create();

        foreach ($seeds_filenames as $filename) {
            $seeds = include $filename;
            $seed_collection = array_merge($seeds, $seed_collection);
        }

        try {
            foreach ($seed_collection as $table => $seeds) {
                $n = Database::table($table)->insert($seeds);

                echo Color::red("$n seed".($n > 1 ? 's' : '')." on $table table\n");
            }
        } catch (\Exception $e) {
            echo Color::red($e->getMessage());

            exit(1);
        }

        exit(0);
    }

    /**
     * Launch process
     *
     * @throws \ErrorException
     */
    protected function launch()
    {
        $action = $this->command->getParameter('action');

        if (!in_array($action, ['server', 'console'])) {
            $this->throwFailsCommand('help run');
        }

        $this->$action();
    }

    /**
     * Launch the local server
     *
     * @return void
     */
    protected function server()
    {
        $port = (int) $this->command->options('--port', 5000);

        $hostname = $this->command->options('--host', 'localhost');

        $settings = $this->command->options('--php-settings', false);

        if (is_bool($settings)) {
            $settings = '';
        } else {
            $settings = '-d '.$settings;
        }

        // resource.
        $r = fopen("php://stdout", "w");

        if ($r) {
            fwrite($r, sprintf("[%s] web server start at http://localhost:%s \033[0;31;7mctrl-c for shutdown it\033[00m\n", date('F d Y H:i:s a'), $port));
        }

        fclose($r);

        // lancement du serveur.
        shell_exec(
            "php -S $hostname:$port -t {$this->public_directory} ".$this->serve_filename." $settings"
        );
    }

    /**
     * Launch the REPL
     */
    protected function console()
    {
        if (is_string($this->command->getParameter('--include'))) {
            $this->setBootstrap(
                array_merge($this->bootstrap, [$this->command->getParameter('--include')])
            );
        }

        if (!class_exists('\Psy\Shell')) {
            echo 'Please, insall psy/psysh:@stable with this command "composer require --dev psy/psysh @stable"';

            return;
        }

        $config = new \Psy\Configuration();

        $config->setPrompt('(bow) >> ');

        $config->setUpdateCheck(\Psy\VersionUpdater\Checker::NEVER);

        $shell = new \Psy\Shell($config);

        $shell->setIncludes($this->bootstrap);

        $shell->run();
    }

    /**
     * Allows generate a resource on a controller
     *
     * @return void
     */
    protected function generate()
    {
        $action = $this->command->getParameter('action');

        if (!in_array($action, ['key', 'resource'])) {
            echo Color::red("Bad $action command");

            exit(1);
        }

        $this->command->$action(
            $this->command->getParameter('target')
        );
    }

    /**
     * Remove the caches
     *
     * @return void
     *
     * @throws \ErrorException
     */
    protected function clear()
    {
        if (in_array($this->command->getParameter('target'), ['view', 'cache', 'all'])) {
            throw new \ErrorException(sprintf(''));
        }

        if ($this->command->getParameter('target') == 'cache') {
            $this->unlinks($this->var_directory.'/cache/bow');

            return;
        }

        if ($this->command->getParameter('target') == 'view') {
            $this->unlinks($this->var_directory.'/cache/view');

            return;
        }

        $this->unlinks($this->var_directory.'/cache/bow');

        $this->unlinks($this->var_directory.'/cache/view');
    }

    /**
     * Delete file
     *
     * @param  string $dirname
     * @return void
     */
    private function unlinks($dirname)
    {
        $glob = glob($dirname);

        foreach ($glob as $item) {
            @unlink($item);
        }
    }

    /**
     * Throw fails command
     *
     * @param string $command
     *
     * @throws \ErrorException
     */
    private function throwFailsCommand($command)
    {
        echo Color::green(sprintf('Type "php bow %s" for more information', $command));

        exit(1);
    }

    /**
     * Display global help or helper command.
     *
     * @param  string|null $command
     * @return int
     */
    private function help($command = null)
    {
        if ($command === null) {
            $usage = <<<USAGE

Bow usage: php bow command:action [name]
    [help|--no-plain|--create|--table|--n-seed|--port|--host|--php-settings|-m|--display-sql|--all|--include|--model]

\033[0;32mcommand\033[00m:

 \033[0;33mhelp\033[00m display command helper

 \033[0;32mgenerate\033[00m create a new app key and resources
   \033[0;33mgenerate:resource\033[00m  Create new REST assicate at a controller
   \033[0;33mgenerate:key\033[00m       Create new app key

 \033[0;32madd\033[00m Create a user class
   \033[0;33madd:middleware\033[00m    Create new middleware
   \033[0;33madd:configuration\033[00m Create new configuration
   \033[0;33madd:service\033[00m       Create new service
   \033[0;33madd:controller\033[00m    Create new controller
   \033[0;33madd:model\033[00m         Create new model
   \033[0;33madd:validator\033[00m     Create new validator
   \033[0;33madd:seeder\033[00m        Create new table fake seeder
   \033[0;33madd:migration\033[00m     Create a new migration

 \033[0;32mmigrate\033[00m apply a migration in user model
   \033[0;33mmigrate\033[00m            Make migration
   \033[0;33mmigrate:reset\033[00m      Reset all migration
   \033[0;33mmigrate:rollback\033[00m   Rollback to previous migration

 \033[0;32mclear\033[00m for clear cache information [not supported]
   \033[0;33mclear:view\033[00m        Clear view cached information
   \033[0;33mclear:cache\033[00m       Clear cache information
   \033[0;33mclear:all\033[00m         Clear all cache information
   
 \033[0;32mseed\033[00m Make seeding
   \033[0;33mseed:table\033[00m [table_name]    Make seeding for one table
   \033[0;33mseed:all\033[00m                   Make seeding for all
 
 \033[0;32mrun\033[00m Launch process
    \033[0;33mrun:console\033[00m show psysh php REPL for debug you code.
    \033[0;33mrun:server\033[00m run a local web server.

USAGE;
            echo $usage;
            return 0;
        }

        switch ($command) {
            case 'help':
                echo "\033[0;33mhelp\033[00m display command helper\n";
                break;
            case 'add':
                echo <<<U
\n\033[0;32mcreate\033[00m create a user class\n
    [option]
    --no-plain  Create a plain controller [available in add:controller]
    -m          Create a migration [available in add:model]
    --create    Create a migration for create table [available in add:migration]
    --table     Create a migration for alter table [available in add:migration]

    * you can use --no-plain --with-model in same command

    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:controller name [option]  For create a new controlleur
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:middleware name           For create a new middleware
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:configuration name        For create a new configuration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:service name              For create a new service
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:model name [option]       For create a new model
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:validation name           For create a new validator
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:seeder name [--n-seed=n]  For create a new seeder
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:migration name            For create a new migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add help                      For display this

U;

                break;
            case 'generate':
                echo <<<U
    \n\033[0;32mgenerate\033[00m create a resource and app key
    [option]
    --model=[model_name] Define the usable model

    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate:resource name [option]   For create a new REST controller
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate:key                      For generate a new APP KEY
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate help                     For display this

U;
                break;
            case 'migrate':
                echo <<<U
\n\033[0;32mmigrate\033[00m apply a migration in user model\n

    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migrate           Make migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migrate:reset     Reset all migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migrate:rollback  Rollback to previous migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migrate help      For display this

U;
                break;

            case 'run':
                echo <<<U
\n\033[0;32mrun\033[00m for launch repl and local server\n
    [option]
    run:server [--port=5000] [--host=localhost] [--php-settings="display_errors=on"]
    run:console [--include=filename.php]

   \033[0;33m$\033[00m php \033[0;34mbow\033[00m run:console\033[00m          Show psysh php REPL 
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m run:server\033[00m [option]  Start local developpement server

U;
                break;

            case 'clear':
                echo <<<U
\n\033[0;32mclear\033[00m for clear cache information\n

   \033[0;33m$\033[00m php \033[0;34mbow\033[00m clear:view             Clear view cached information
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m clear:cache\033[00m    Clear cache information
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m clear:all\033[00m      Clear all cache information

U;
                break;

            case 'seed':
                echo <<<U
\n\033[0;32mMake table seeding\033[00m\n

   \033[0;33m$\033[00m php \033[0;34mbow\033[00m seed:all\033[00m               Make seeding for all
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m seed:table\033[00m table_name  Make seeding for one table

U;
                break;
        }

        exit(0);
    }
}
