<?php

declare(strict_types=1);

namespace Bow\Console;

use Bow\Configuration\Loader;
use Bow\Console\Exception\ConsoleException;
use Bow\Console\Traits\ConsoleTrait;
use ErrorException;
use Exception;

/**
 * @method static Console addCommand(string $command, callable $cb)
 */
class Console
{
    use ConsoleTrait;

    /**
     * Define Bow Framework version.
     *
     * @var string
     */
    private const VERSION = '5.x';

    /**
     * Command aliases that share another topic's help body.
     */
    private const HELP_TOPIC_ALIASES = [
        'gen' => 'generate',
    ];

    /**
     * Per-topic help bodies, keyed by command (or alias). The "gen" alias
     * shares the "generate" body via the aliases map below.
     *
     * Bodies use raw ANSI escape codes — they are pre-formatted templates
     * rather than messages composed through Color::*.
     */
    private const HELP_TOPICS = [
        'add' => <<<U
\n\033[0;32mcreate\033[00m create a user class\n
    [option]
    --no-plain  Create a plain controller [available in add:controller]
    -m          Create a migration [available in add:model]
    --create    Create a migration for create table [available in add:migration]
    --table     Create a migration for alter table [available in add:migration]

    * you can use --no-plain --with-model in same command

    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:controller name [option]  Create a new controller
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:middleware name           Create a new middleware
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:configuration name        Create a new configuration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:service name              Create a new service
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:exception name            Create a new exception
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:model name [option]       Create a new model
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:validation name           Create a new validation
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:seeder name [--seed=n]    Create a new seeder
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:migration name            Create a new migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:event name                Create a new event listener
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:task name                  Create a new queue task
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:command name              Create a new console command
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add:notifier name              Create a new messaging handler
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m add help                      Display this help

U,
        'generate' => <<<U
    \n\033[0;32mgenerate\033[00m create a resource and app key
    [option]
    --model=[model_name] Define the usable model

    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate:resource name [option]   Create a new REST controller
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate:session-table            Generate the table for session
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate:cache-table              Generate the table for cache
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate:queue-table              Generate the table for queue
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate:notification-table       Generate the table for notification
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate:key                      Generate a new APP KEY
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m generate help                     Display this help
    \033[0;33mgen\033[00m                                                           Alias of \033[0;33mgenerate\033[00m

U,
        'migration' => <<<U
\n\033[0;32mmigration\033[00m apply a migration in user model\n

    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migration:migrate   Run migrations
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migration:reset     Reset all migrations
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migration:rollback  Rollback to previous migration
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migrate             Alias of \033[0;33mmigration:migrate\033[00m
    \033[0;33m$\033[00m php \033[0;34mbow\033[00m migration help      Display this help

U,
        'run' => <<<U
\n\033[0;32mrun\033[00m for launch repl and local server\n
    [option]
    run:server [--port=8080] [--host=localhost] [--php-settings="display_errors=on"]
    run:console [--include=filename.php] [--prompt=prompt_name]
    run:worker [--queue=default] [--connexion=beanstalkd,sqs,redis,database] [--tries=duration] [--sleep=duration] [--timeout=duration]

   \033[0;33m$\033[00m php \033[0;34mbow\033[00m run:console          Show PsySH PHP REPL for debugging code
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m run:server [option]  Start local development server
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m run:worker [option]  Start worker to handle queue tasks

U,
        'clear' => <<<U
\n\033[0;32mclear\033[00m for clear cache information\n

   \033[0;33m$\033[00m php \033[0;34mbow\033[00m clear:view             Clear view cached information
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m clear:cache\033[00m    Clear cache information
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m clear:all\033[00m      Clear all cache information

U,
        'seed' => <<<U
\n\033[0;32mMake table seeding\033[00m\n

   \033[0;33m$\033[00m php \033[0;34mbow\033[00m seed:all\033[00m               Make seeding for all
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m seed:file\033[00m class_name  Make seeding for one file

U,
        'flush' => <<<U
\n\033[0;32mFlush all queues content\033[00m\n
    [option]
    flush:worker [connection] [--queue=queue_name]

   \033[0;33m$\033[00m php \033[0;34mbow\033[00m flush:worker\033[00m           Flush all queues

U,
        'schedule' => <<<U
\n\033[0;32mTask scheduling commands\033[00m\n
    [commands]
    schedule:run            Run the scheduler once (execute all due tasks)
    schedule:work           Start the scheduler daemon (continuous loop)
    schedule:list           List all registered scheduled tasks
    schedule:next           Show the next run time for all tasks
    schedule:test [class]   Test run a specific task by class name

   \033[0;33m$\033[00m php \033[0;34mbow\033[00m schedule:run\033[00m           Run due tasks once
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m schedule:work\033[00m          Start scheduler daemon
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m schedule:list\033[00m          List all scheduled tasks
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m schedule:next\033[00m          Show next run times
   \033[0;33m$\033[00m php \033[0;34mbow\033[00m schedule:test TaskClass\033[00m  Test a specific task

U,
    ];


    /**
     * The command list
     *
     * @var array
     */
    private const COMMAND = [
        'add',
        'migration',
        'migrate',
        'run',
        'generate',
        'gen',
        'seed',
        'help',
        'clear',
        'flush',
        'launch',
        'serve',
        'schedule',
    ];

    /**
     * The action list
     *
     * @var array
     */
    private const ADD_ACTION = [
        'middleware',
        'controller',
        'model',
        'validation',
        'seeder',
        'migration',
        'configuration',
        'service',
        'exception',
        'event',
        'task',
        'scheduler',
        'command',
        'listener',
        'notifier'
    ];

    /**
     * The custom command registers
     *
     * @var array
     */
    private static array $registers = [];

    /**
     * The console instance
     *
     * @var ?Console
     */
    private static ?Console $instance = null;

    /**
     * The Setting instance
     *
     * @var Setting
     */
    private Setting $setting;

    /**
     * The Command instance
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
     * Define if console booted
     *
     * @var bool
     */
    private bool $booted = false;

    /**
     * The Argument instance
     *
     * @var Argument
     */
    private Argument $arg;

    /**
     * Bow constructor.
     *
     * @param Setting $setting
     */
    public function __construct(Setting $setting)
    {
        $this->arg = new Argument();

        if ($this->arg->hasTrash()) {
            $this->throwFailsCommand('Bad command usage', 'help');
        }

        $this->setting = $setting;

        $this->command = new Command($setting, $this->arg);

        static::$instance = $this;
    }

    /**
     * Get the console instance
     *
     * @return ?Console
     * @throws ConsoleException
     */
    public static function getInstance(): ?Console
    {
        if (is_null(static::$instance)) {
            throw new ConsoleException("The console is not instantiated");
        }

        return static::$instance;
    }

    /**
     * Add a custom order to the store from the web env
     * This method work on web and cli env
     *
     * @param  string          $command     Command name (or `base:action` form)
     * @param  callable|string $cb          Closure / function name / class string
     * @param  string|null     $description One-liner shown in the global help index
     * @param  string|null     $help        Full body shown by `php bow help <command>`
     * @return void
     */
    public static function register(
        string $command,
        callable|string $cb,
        ?string $description = null,
        ?string $help = null,
    ): void {
        static::$registers[$command] = [
            'cb'          => $cb,
            'description' => $description,
            'help'        => $help,
        ];
    }

    /**
     * Bind kernel
     *
     * @param  Loader $kernel
     * @return void
     */
    public function bind(Loader $kernel): void
    {
        $this->kernel = $kernel;
    }

    /**
     * Launch Bow task runner
     *
     * @throws
     */
    public function run()
    {
        if ($this->booted) {
            exit(0);
        }

        // Boot kernel and console
        $this->kernel->withoutSession();

        try {
            $this->kernel->boot();
        } catch (Exception $exception) {
            echo Color::red($exception->getMessage());
            echo Color::green($exception->getTraceAsString());

            exit(1);
        }

        $this->booted = true;

        // Run all bootstraps files
        foreach ($this->setting->getBootstrap() as $item) {
            include $item;
        }

        // Get the argument command
        $command = $this->arg->getCommand();

        if ($command == 'run') {
            $command = 'launch';
        }

        try {
            $this->call($command);
            exit(0);
        } catch (Exception $exception) {
            echo Color::red($exception->getMessage());
            echo Color::green($exception->getTraceAsString());

            exit(1);
        }
    }

    /**
     * Calls a command
     *
     * @param  string|null $command
     * @return mixed
     * @throws ErrorException
     * @throws Exception
     */
    public function call(?string $command): mixed
    {
        // Display of the help menu if no command defined.
        if (!isset($command)) {
            $this->help();
            exit(0);
        }

        // The built-in commands have priority
        $commands = $this->command->getCommands();

        if (!in_array($command, array_keys($commands))) {
            // Try to execute the custom command
            if (array_key_exists($this->arg->getRawCommand(), static::$registers) || array_key_exists($command, static::$registers)) {
                // `php bow <custom> help` shows the registered help instead of running it.
                if ($this->arg->getTarget() === 'help' && !$this->arg->getAction()) {
                    $this->help($command);
                    exit(0);
                }

                return $this->executeCustomCommand($this->arg->getRawCommand() ?? $command);
            }
        }

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
            return call_user_func_array([$this, $command], [$this->arg->getRawCommand()]);
        } catch (Exception $e) {
            echo Color::red(sprintf("$command command failed with: %s\n", $e->getMessage()));
            exit(1);
        }
    }

    /**
     * Execute the define custom command
     *
     * @param  string $command
     * @return mixed
     * @throws Exception
     */
    private function executeCustomCommand(string $command): mixed
    {
        try {
            $classname = static::$registers[$command]['cb'];

            if (is_callable($classname)) {
                return $classname($this->arg, $this->setting);
            }

            // Create the command instance
            $instance = new $classname($this->setting, $this->arg);

            return call_user_func_array([$instance, "process"], []);
        } catch (Exception $exception) {
            if (php_sapi_name() !== "cli") {
                throw $exception;
            }

            echo Color::red($exception->getMessage());
            echo Color::green($exception->getTraceAsString());

            exit(1);
        }
    }

    /**
     * Add a custom order to the store
     * The method work only on cli env
     *
     * @param  string          $command
     * @param  callable|string $cb
     * @param  string|null     $description One-liner shown in the global help index
     * @param  string|null     $help        Full body shown by `php bow help <command>`
     * @return Console
     */
    public function addCommand(
        string $command,
        callable|string $cb,
        ?string $description = null,
        ?string $help = null,
    ): Console {
        static::$registers[$command] = [
            'cb'          => $cb,
            'description' => $description,
            'help'        => $help,
        ];

        return $this;
    }

    /**
     * Launch a migration
     *
     * @return void
     * @throws ErrorException
     */
    private function migration(): void
    {
        $action = $this->arg->getAction();

        if (!in_array($action, ['migrate', 'rollback', 'reset'])) {
            $this->throwFailsCommand('This action is not exists!', 'help migration');
        }

        $this->command->call("migration:{$action}", $action, $action);
    }

    /**
     * Launch a migration
     *
     * @return void
     * @throws ErrorException
     */
    private function migrate(): void
    {
        $action = $this->arg->getAction();

        if (!is_null($action)) {
            $this->throwFailsCommand('This action is not allow!', 'help migration');
        }

        $this->command->call('migration:migrate', 'migrate', null);
    }

    /**
     * Create files
     *
     * @return void
     * @throws ErrorException
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

        $this->command->call("add:{$action}", $action, $target);
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

        if (!in_array($action, ['all', 'file'])) {
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

        $this->command->call("seed:{$action}", $action, $target);
    }

    /**
     * Launch process
     *
     * @throws ErrorException
     */
    private function launch(): void
    {
        $action = $this->arg->getAction();

        if (!in_array($action, ['server', 'console', 'worker'])) {
            $this->throwFailsCommand('Bad command usage', 'help run');
        }

        $this->command->call("run:{$action}", $action, $this->arg->getTarget());
    }

    /**
     * Alias of run:server
     *
     * @return void
     * @throws ErrorException
     */
    private function serve(): void
    {
        $this->command->call("run:server", 'server', $this->arg->getTarget());
    }

    /**
     * Handle scheduler commands
     *
     * @return void
     * @throws ErrorException
     */
    private function schedule(): void
    {
        $action = $this->arg->getAction();

        if (!in_array($action, ['run', 'work', 'list', 'next', 'test'])) {
            $this->throwFailsCommand('Bad command usage', 'help schedule');
        }

        $this->command->call("schedule:{$action}", $action, $this->arg->getTarget());
    }

    /**
     * Alias of generate
     *
     * @return void
     * @throws ErrorException
     */
    private function gen(): void
    {
        $this->generate();
    }

    /**
     * Allows to generate a resource on a controller
     *
     * @return void
     * @throws ErrorException
     */
    private function generate(): void
    {
        $action = $this->arg->getAction();

        if (!in_array($action, ['key', 'resource', 'notification-table', 'session-table', 'cache-table', 'queue-table'])) {
            $this->throwFailsCommand('This action is not exists', 'help generate');
        }

        $this->command->call("generate:{$action}", $action, $this->arg->getTarget());
    }

    /**
     * Remove the caches
     *
     * @return void
     * @throws ErrorException
     */
    private function clear(): void
    {
        $action = $this->arg->getAction();

        $this->command->call('clear', $action, $action);
    }

    /**
     * Flush the connections
     *
     * @return void
     * @throws ErrorException
     */
    private function flush(): void
    {
        $action = $this->arg->getAction();

        if ($action != 'worker') {
            $this->throwFailsCommand('This action is not exists', 'help flush');
        }

        $this->command->call('flush:worker', $action);
    }

    /**
     * Show bow framework version and current php version in console
     *
     * @return void
     */
    private function getVersion(): void
    {
        $version = <<<USAGE
Console running for Bow Framework: \033[0;32m%s\033[00m - PHP Version: \033[0;32m%s\033[0;33m

USAGE;
        echo sprintf($version, Console::VERSION, PHP_VERSION);
    }

    /**
     * Display global help or a single topic's help.
     */
    private function help(?string $command = null): void
    {
        $this->getVersion();

        if ($command === null || $command === 'help') {
            $this->printGlobalHelp();
            return;
        }

        $this->printTopicHelp($command);
    }

    /**
     * Print the top-level command index.
     */
    private function printGlobalHelp(): void
    {
        echo <<<USAGE

Bow task runner usage: php bow command:action [name] --option

\033[0;32mCOMMAND\033[00m:

 \033[0;33mhelp\033[00m Display command helper

 \033[0;32mGENERATE\033[00m Create new app key and resources
   \033[0;33mgenerate:resource\033[00m             Create new REST controller
   \033[0;33mgenerate:session-table\033[00m        Generate preset table for session
   \033[0;33mgenerate:cache-table\033[00m          Generate preset table for cache
   \033[0;33mgenerate:queue-table\033[00m          Generate preset table for queue
   \033[0;33mgenerate:notification-table\033[00m   Generate preset table for notification
   \033[0;33mgenerate:key\033[00m                  Create new app key

 \033[0;32mADD\033[00m Create a user class
   \033[0;33madd:middleware\033[00m      Create new middleware
   \033[0;33madd:configuration\033[00m    Create new configuration
   \033[0;33madd:service\033[00m         Create new service
   \033[0;33madd:exception\033[00m       Create new exception
   \033[0;33madd:controller\033[00m      Create new controller
   \033[0;33madd:model\033[00m           Create new model
   \033[0;33madd:validation\033[00m      Create new validation
   \033[0;33madd:seeder\033[00m          Create new table fake seeder
   \033[0;33madd:migration\033[00m       Create a new migration
   \033[0;33madd:event\033[00m           Create a new event
   \033[0;33madd:listener\033[00m        Create a new event listener
   \033[0;33madd:task\033[00m             Create a new task
   \033[0;33madd:command\033[00m         Create a new console command
   \033[0;33madd:notifier\033[00m         Create a new messaging handler

 \033[0;32mMIGRATION\033[00m Apply migration to database
   \033[0;33mmigration:migrate\033[00m   Run migrations
   \033[0;33mmigration:reset\033[00m     Reset all migrations
   \033[0;33mmigration:rollback\033[00m  Rollback to previous migration
   \033[0;33mmigrate\033[00m             Alias of \033[0;33mmigration:migrate\033[00m

 \033[0;32mCLEAR\033[00m Clear cache information
   \033[0;33mclear:view\033[00m          Clear view cached files
   \033[0;33mclear:cache\033[00m         Clear cache files
   \033[0;33mclear:session\033[00m       Clear session cache files
   \033[0;33mclear:log\033[00m           Clear log files
   \033[0;33mclear:all\033[00m           Clear all cache files

 \033[0;32mSEED\033[00m Run database seeders
   \033[0;33mseed:file\033[00m [class_name] Run specific seeder file
   \033[0;33mseed:all\033[00m              Run all seeders

 \033[0;32mFLUSH\033[00m Drain queue workers
   \033[0;33mflush:worker\033[00m        Flush all queues

 \033[0;32mRUN\033[00m Launch development tools
   \033[0;33mrun:console\033[00m Show PsySH PHP REPL for debugging code
   \033[0;33mrun:server\033[00m  Start local development server
   \033[0;33mrun:worker\033[00m  Start consumer/worker to handle queue tasks

 \033[0;32mSCHEDULE\033[00m Task scheduling commands
   \033[0;33mschedule:run\033[00m   Run the scheduler once (execute all due tasks)
   \033[0;33mschedule:work\033[00m  Start the scheduler daemon (continuous loop)
   \033[0;33mschedule:list\033[00m  List all registered scheduled tasks
   \033[0;33mschedule:next\033[00m  Show the next run time for all tasks
   \033[0;33mschedule:test\033[00m  Test run a specific task by class name

USAGE;

        $this->printCustomCommandsSection();
    }

    /**
     * Append the CUSTOM section listing application-registered commands.
     *
     * Each entry shows the command name in yellow and, when available, the
     * description supplied to register() / addCommand(). Pad the name column
     * to the widest entry so descriptions align in the terminal.
     */
    private function printCustomCommandsSection(): void
    {
        if (static::$registers === []) {
            return;
        }

        $names = array_keys(static::$registers);
        $width = max(array_map('strlen', $names));

        echo "\n \033[0;32mCUSTOM\033[00m Application-registered commands\n";

        foreach (static::$registers as $name => $entry) {
            $description = (string) ($entry['description'] ?? '');
            echo sprintf(
                "   \033[0;33m%s\033[00m  %s\n",
                str_pad($name, $width),
                $description,
            );
        }

        echo "\n";
    }

    /**
     * Print help for a single topic. Resolution order:
     *
     *   1. Built-in topics in HELP_TOPICS (including HELP_TOPIC_ALIASES).
     *   2. Application-registered commands that supplied a help body via
     *      register()/addCommand().
     *   3. Otherwise: error.
     */
    private function printTopicHelp(string $command): void
    {
        $topic = self::HELP_TOPIC_ALIASES[$command] ?? $command;

        if (isset(self::HELP_TOPICS[$topic])) {
            echo self::HELP_TOPICS[$topic];
            return;
        }

        $registered = static::$registers[$command] ?? null;

        if (is_array($registered) && is_string($registered['help'] ?? null)) {
            echo $registered['help'];
            return;
        }

        $this->throwFailsCommand('Please make php bow help for show whole docs !');
    }
}
