<?php

declare(strict_types=1);

namespace Bow\Console;

use Bow\Configuration\Loader;
use Bow\Console\Traits\ConsoleTrait;

class Console
{
    use ConsoleTrait;

    /**
     * The Setting instance
     *
     * @var Setting
     */
    private Setting $setting;

    /**
     * The COMMAND instance
     *
     * @var Command
     */
    private Command $command;

    /**
     * The Loader instance
     *
     * @var Loader
     */
    private Loader $kernel;

    /**
     * The custom command registers
     *
     * @var array
     */
    private array $registers = [];

    /**
     * Defines if console booted
     *
     * @var bool
     */
    private bool $booted = false;

    /**
     * The Argument instance
     *
     * @return Argument
     */
    private Argument $arg;

    /**
     * The command list
     *
     * @var array
     */
    const COMMAND = [
        'add', 'migration', 'migrate', 'run', 'generate', 'gen', 'seed', 'help', 'launch', 'clear'
    ];

    /**
     * The action list
     *
     * @var array
     */
    const ADD_ACTION = [
        'middleware', 'controller', 'model', 'validation',
        'seeder', 'migration', 'configuration', 'service',
        'exception', 'event', 'producer', 'command', 'listener'
    ];

    /**
     * Bow constructor.
     *
     * @param  Setting $setting
     *
     * @return void
     */
    public function __construct(Setting $setting)
    {
        $this->arg = new Argument;

        if ($this->arg->hasTrash()) {
            $this->throwFailsCommand('Bad command usage', 'help');
        }

        $this->setting = $setting;

        $this->command = new Command($setting, $this->arg);
    }

    /**
     * Bind kernel
     *
     * @param Loader $kernel
     * @return void
     */
    public function bind(Loader $kernel): void
    {
        $this->kernel = $kernel;
    }

    /**
     * Launch Bow task runner
     *
     * @return void
     * @throws
     */
    public function run(): mixed
    {
        if ($this->booted) {
            return false;
        }

        // Boot kernel and console
        $this->kernel->withoutSession();

        try {
            $this->kernel->boot();
        } catch (\Exception $exception) {
            echo Color::red($exception->getMessage());
            echo Color::green($exception->getTraceAsString());
            
            exit(1);
        }
        
        $this->booted = true;

        foreach ($this->setting->getBootstrap() as $item) {
            require $item;
        }
        
        $command = $this->arg->getCommand();

        if (array_key_exists($command, $this->registers)) {
            try {
                $classname = $this->registers[$command];

                if (is_callable($classname)) {
                    return $classname($this->arg);
                }
                
                $instance = new $classname($this->setting, $this->arg);
                
                return call_user_func_array([$instance, "process"], []);
            } catch (\Exception $exception) {
                echo Color::red($exception->getMessage());
                echo Color::green($exception->getTraceAsString());

                exit(1);
            }
        }

        if ($command == 'launch') {
            $command = null;
        }

        if ($command == 'run') {
            $command = 'launch';
        }

        try {
            $this->call($command);
        } catch (\Exception $exception) {
            echo Color::red($exception->getMessage());
            echo Color::green($exception->getTraceAsString());

            exit(1);
        }
    }

    /**
     * Calls a command
     *
     * @param  string $command
     * @return void
     * @throws
     */
    private function call(string $command): void
    {
        if (!in_array($command, static::COMMAND)) {
            $this->throwFailsCommand("The command '$command' not exists.", 'help');
        }

        $target = $this->arg->getTarget();

        if (!$this->arg->getAction()) {
            if ($target == 'help') {
                $this->help($command);

                exit(0);
            }
        }

        try {
            call_user_func_array(
                [$this, $command],
                [$target]
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
     * @return Console
     */
    public function addCommand($command, $cb): Console
    {
        $this->registers[$command] = $cb;

        return $this;
    }

    /**
     * Launch a migration
     *
     * @return void
     *
     * @throws \ErrorException
     */
    private function migration(): void
    {
        $action = $this->arg->getAction();

        if (!in_array($action, ['migrate', 'rollback', 'reset'])) {
            $this->throwFailsCommand('This action is not exists!', 'help migration');
        }

        $target = $this->arg->getTarget();

        $this->command->call('migration', $action, $target);
    }

    /**
     * Launch a migration
     *
     * @return void
     * @throws \ErrorException
     */
    private function migrate(): void
    {
        $action = $this->arg->getAction();

        if (!is_null($action)) {
            $this->throwFailsCommand('This action is not allow!', 'help migration');
        }

        $this->command->call('migration', 'migrate', null);
    }

    /**
     * Create files
     *
     * @return void
     * @throws \ErrorException
     */
    private function add(): void
    {
        $action = $this->arg->getAction();

        if (!in_array($action, static::ADD_ACTION)) {
            $this->throwFailsCommand('This action is not exists', 'help add');
        }

        $target = $this->arg->getTarget();

        if (is_null($target)) {
            $this->throwFailsCommand('Please provide the filename', 'help add');
        }

        $this->command->call('add', $action, $target);
    }

    /**
     * Launch seeding
     *
     * @return void
     * @throws
     */
    private function seed(): void
    {
        $action = $this->arg->getAction();

        if (!in_array($action, ['all', 'table'])) {
            $this->throwFailsCommand('This action is not exists', 'help seed');
        }

        $target = $this->arg->getTarget();

        if ($action == 'all') {
            if ($target != null) {
                $this->throwFailsCommand(
                    'Bad command usage target is not allow in this case',
                    'help seed'
                );
            }
        }

        $this->command->call('seeder', $action, $target);
    }

    /**
     * Launch process
     *
     * @throws \ErrorException
     */
    private function launch(): void
    {
        $action = $this->arg->getAction();

        if (!in_array($action, ['server', 'console', 'worker'])) {
            $this->throwFailsCommand('Bad command usage', 'help run');
        }

        $target = $this->arg->getTarget();

        $this->command->call('runner', $action, $target);
    }

    /**
     * Allows generate a resource on a controller
     *
     * @return void
     */
    private function generate(): void
    {
        $action = $this->arg->getAction();

        if (!in_array($action, ['key', 'resource', 'session'])) {
            $this->throwFailsCommand('This action is not exists', 'help generate');
        }

        $target = $this->arg->getTarget();

        $this->command->call('generator', $action, $target);
    }

    /**
     * Alias of generate
     *
     * @return void
     */
    private function gen(): void
    {
        $this->generate();
    }

    /**
     * Remove the caches
     *
     * @return void
     * @throws \ErrorException
     */
    private function clear(): void
    {
        $action = $this->arg->getAction();

        $this->command->call('clear', "make", $action);
    }

    /**
     * Display global help or helper command.
     *
     * @param  string|null $command
     * @return int
     */
    private function help(?string $command = null): int
    {
        if ($command === null) {
            $usage = <<<USAGE
Bow task runner usage: php bow command:action [name] --option

\033[0;32mCOMMAND\033[00m:

 \033[0;33mhelp\033[00m display command helper

 \033[0;32mGENERATE\033[00m create a new app key and resources
   \033[0;33mgenerate:resource\033[00m   Create new REST controller
   \033[0;33mgenerate:session\033[00m    For generate session table
   \033[0;33mgenerate:key\033[00m        Create new app key

 \033[0;32mADD\033[00m Create a user class
   \033[0;33madd:middleware\033[00m      Create new middleware
   \033[0;33madd:configuration\033[00m   Create new configuration
   \033[0;33madd:service\033[00m         Create new service
   \033[0;33madd:exception\033[00m       Create new exception
   \033[0;33madd:controller\033[00m      Create new controller
   \033[0;33madd:model\033[00m           Create new model
   \033[0;33madd:validation\033[00m      Create new validation
   \033[0;33madd:seeder\033[00m          Create new table fake seeder
   \033[0;33madd:migration\033[00m       Create a new migration
   \033[0;33madd:event\033[00m           Create a new event
   \033[0;33madd:listener\033[00m        Create a new event listener
   \033[0;33madd:producer\033[00m        Create a new producer
   \033[0;33madd:command\033[00m         Create a new bow console command

 \033[0;32mMIGRATION\033[00m apply a migration in user model
   \033[0;33mmigration:migrate\033[00m   Make migration
   \033[0;33mmigration:reset\033[00m     Reset all migration
   \033[0;33mmigration:rollback\033[00m  Rollback to previous migration
   \033[0;33mmigrate\033[00m             Alias of \033[0;33mmigration:migrate\033[00m

 \033[0;32mCLEAR\033[00m for clear cache information
   \033[0;33mclear:view\033[00m          Clear view cached information
   \033[0;33mclear:cache\033[00m         Clear cache information
   \033[0;33mclear:session\033[00m       Clear session cache information
   \033[0;33mclear:log\033[00m           Clear logs cache information
   \033[0;33mclear:all\033[00m           Clear all cache information

 \033[0;32mSEED\033[00m Make seeding
   \033[0;33mseed:table\033[00m [name]   Make seeding for one table
   \033[0;33mseed:all\033[00m            Make seeding for all
 
 \033[0;32mRUN\033[00m Launch process
   \033[0;33mrun:console\033[00m show psysh php REPL for debug you code.
   \033[0;33mrun:server\033[00m  run a local web server.
   \033[0;33mrun:worker\033[00m  run a consumer/worker for handle queue.

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
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:exception name            For create a new exception
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:model name [option]       For create a new model
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:validation name           For create a new validation
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:seeder name [--seed=n]    For create a new seeder
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:migration name            For create a new migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:event name                For create a new event listener
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:producer name             For create a new queue producer
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:command name              For create a new bow console command
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add help                      For display this

U;

                break;
            case 'generate':
                echo <<<U
    \n\033[0;32mgenerate\033[00m create a resource and app key
    [option]
    --model=[model_name] Define the usable model

    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate:resource name [option]   For create a new REST controller
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate:session                  For generate session table
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate:key                      For generate a new APP KEY
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate help                     For display this

U;
                break;
            case 'migration':
                echo <<<U
\n\033[0;32mmigration\033[00m apply a migration in user model\n

    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migration:migrate   Make migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migration:reset     Reset all migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migration:rollback  Rollback to previous migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migrate             Alias of \033[0;33mmigration:migrate\033[00m
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migration help      For display this

U;
                break;

            case 'run': // phpcs:disable
                echo <<<U
\n\033[0;32mrun\033[00m for launch repl and local server\n
    [option]
    run:server [--port=5000] [--host=localhost] [--php-settings="display_errors=on"]
    run:console [--include=filename.php] [--prompt=prompt_name]
    run:worker [--queue=default] [--connexion=beanstalkd,sqs] [--retry-after=duration]

   \033[0;33m$\033[00m php \033[0;34mbow\033[00m run:console\033[00m          Show psysh php REPL 
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m run:server\033[00m [option]  Start local developpement server
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m run:worker\033[00m [option]  Start worker/consumer for handle the producer

U; // phpcs:enable
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
