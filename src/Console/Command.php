<?php

declare(strict_types=1);

namespace Bow\Console;

use ErrorException;
use Bow\Console\Command\ReplCommand;
use Bow\Console\Command\ClearCommand;
use Bow\Console\Command\SeederCommand;
use Bow\Console\Command\ServerCommand;
use Bow\Console\Command\WorkerCommand;
use Bow\Console\Command\AppEventCommand;
use Bow\Console\Command\MigrationCommand;
use Bow\Console\Command\ValidationCommand;
use Bow\Console\Command\GenerateKeyCommand;
use Bow\Console\Command\GenerateCacheCommand;
use Bow\Console\Command\GenerateModelCommand;
use Bow\Console\Command\GenerateQueueCommand;
use Bow\Console\Command\GenerateSeederCommand;
use Bow\Console\Command\GenerateConsoleCommand;
use Bow\Console\Command\GenerateServiceCommand;
use Bow\Console\Command\GenerateSessionCommand;
use Bow\Console\Command\GenerateProducerCommand;
use Bow\Console\Command\GenerateExceptionCommand;
use Bow\Console\Command\GenerateMessagingCommand;
use Bow\Console\Command\GenerateControllerCommand;
use Bow\Console\Command\GenerateMiddlewareCommand;
use Bow\Console\Command\GenerateNotificationCommand;
use Bow\Console\Command\GenerateConfigurationCommand;
use Bow\Console\Command\GenerateEventListenerCommand;
use Bow\Console\Command\GenerateResourceControllerCommand;

class Command extends AbstractCommand
{
    /**
     * List of command actions
     *
     * @var array
     */
    private array $commands = [
        "seed" => SeederCommand::class,
        "seed:table" => GenerateSeederCommand::class,
        "serve" => ServerCommand::class,
        "clear" => ClearCommand::class,
        "migrate" => MigrationCommand::class,
        "migration:migrate" => MigrationCommand::class,
        "migration:rollback" => MigrationCommand::class,
        "migration:reset" => MigrationCommand::class,
        "add:controller" => GenerateControllerCommand::class,
        "add:configuration" => GenerateConfigurationCommand::class,
        "add:exception" => GenerateExceptionCommand::class,
        "add:middleware" => GenerateMiddlewareCommand::class,
        "add:migration" => MigrationCommand::class,
        "add:model" => GenerateModelCommand::class,
        "add:seeder" => SeederCommand::class,
        "add:service" => GenerateServiceCommand::class,
        "add:validation" => ValidationCommand::class,
        "add:event" => AppEventCommand::class,
        "add:listener" => GenerateEventListenerCommand::class,
        "add:producer" => GenerateProducerCommand::class,
        "add:command" => GenerateConsoleCommand::class,
        "add:message" => GenerateMessagingCommand::class,
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

        if (!preg_match('/^(migrate|migration)/', $command)) {
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
