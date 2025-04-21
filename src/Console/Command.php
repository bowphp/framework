<?php

declare(strict_types=1);

namespace Bow\Console;

use ErrorException;
use Bow\Support\Str;
use Bow\Console\Command\ReplCommand;
use Bow\Console\Command\ClearCommand;
use Bow\Console\Command\ModelCommand;
use Bow\Console\Command\SeederCommand;
use Bow\Console\Command\ServerCommand;
use Bow\Console\Command\WorkerCommand;
use Bow\Console\Command\ConsoleCommand;
use Bow\Console\Command\ServiceCommand;
use Bow\Console\Command\AppEventCommand;
use Bow\Console\Command\ProducerCommand;
use Bow\Console\Command\ExceptionCommand;
use Bow\Console\Command\MessagingCommand;
use Bow\Console\Command\MigrationCommand;
use Bow\Console\Command\ControllerCommand;
use Bow\Console\Command\MiddlewareCommand;
use Bow\Console\Command\ValidationCommand;
use Bow\Console\Command\GenerateKeyCommand;
use Bow\Console\Command\ConfigurationCommand;
use Bow\Console\Command\EventListenerCommand;
use Bow\Console\Command\GenerateCacheCommand;
use Bow\Console\Command\GenerateQueueCommand;
use Bow\Console\Command\GenerateSessionCommand;
use Bow\Console\Command\GenerateNotificationCommand;
use Bow\Console\Command\GenerateResourceControllerCommand;

class Command extends AbstractCommand
{
    /**
     * List of command actions
     *
     * @var array
     */
    private array $commands = [
        "clear" => ClearCommand::class,
        "migration" => MigrationCommand::class,
        "migrate" => MigrationCommand::class,
        "seed" => SeederCommand::class,
        "serve" => ServerCommand::class,
        "add:controller" => ControllerCommand::class,
        "add:configuration" => ConfigurationCommand::class,
        "add:exception" => ExceptionCommand::class,
        "add:middleware" => MiddlewareCommand::class,
        "add:migration" => MigrationCommand::class,
        "add:model" => ModelCommand::class,
        "add:seeder" => SeederCommand::class,
        "add:service" => ServiceCommand::class,
        "add:validation" => ValidationCommand::class,
        "add:event" => AppEventCommand::class,
        "add:listener" => EventListenerCommand::class,
        "add:producer" => ProducerCommand::class,
        "add:command" => ConsoleCommand::class,
        "add:message" => MessagingCommand::class,
        "run:console" => ReplCommand::class,
        "run:server" => ServerCommand::class,
        "run:worker" => WorkerCommand::class,
        "flush:worker" => WorkerCommand::class,
        "generate:key" => GenerateKeyCommand::class,
        "generate:resource" => GenerateResourceControllerCommand::class,
        "generate:session-table" => GenerateSessionCommand::class,
        "generate:queue-table" => GenerateQueueCommand::class,
        "generate:cache-table" => GenerateCacheCommand::class,
        "generate:notification-table" => GenerateNotificationCommand::class,
    ];

    /**
     * Get the commands
     *
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * The call command
     *
     * @param  string $action
     * @param  string $command
     * @param  array  $rest
     * @return mixed
     * @throws ErrorException
     */
    public function call(string $command, string $action, ...$rest): mixed
    {
        $class = $this->commands[$command] ?? null;

        if (is_null($class)) {
            $this->throwFailsCommand("The command $command not found !");
        }

        if (!preg_match('/^(clear|migrate|migration):/', $command)) {
            $method = "run";
        } else {
            $method = $action;
        }

        $instance = new $class($this->setting, $this->arg);

        if (method_exists($instance, $method)) {
            return call_user_func_array([$instance, $method], $rest);
        }

        return null;
    }
}
