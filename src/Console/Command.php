<?php

declare(strict_types=1);

namespace Bow\Console;

use ErrorException;
use Bow\Console\Command\ReplCommand;
use Bow\Console\Command\ClearCommand;
use Bow\Console\Command\SeederCommand;
use Bow\Console\Command\ServerCommand;
use Bow\Console\Command\WorkerCommand;
use Bow\Console\Command\MigrationCommand;
use Bow\Console\Command\Generator\GenerateKeyCommand;
use Bow\Console\Command\Generator\GenerateCacheCommand;
use Bow\Console\Command\Generator\GenerateModelCommand;
use Bow\Console\Command\Generator\GenerateQueueCommand;
use Bow\Console\Command\Generator\GenerateSeederCommand;
use Bow\Console\Command\Generator\GenerateConsoleCommand;
use Bow\Console\Command\Generator\GenerateServiceCommand;
use Bow\Console\Command\Generator\GenerateSessionCommand;
use Bow\Console\Command\Generator\GenerateAppEventCommand;
use Bow\Console\Command\Generator\GenerateExceptionCommand;
use Bow\Console\Command\Generator\GenerateMessagingCommand;
use Bow\Console\Command\Generator\GenerateMigrationCommand;
use Bow\Console\Command\Generator\GenerateControllerCommand;
use Bow\Console\Command\Generator\GenerateMiddlewareCommand;
use Bow\Console\Command\Generator\GenerateValidationCommand;
use Bow\Console\Command\Generator\GenerateNotificationCommand;
use Bow\Console\Command\Generator\GenerateConfigurationCommand;
use Bow\Console\Command\Generator\GenerateEventListenerCommand;
use Bow\Console\Command\Generator\GenerateJobCommand;
use Bow\Console\Command\Generator\GenerateRouterResourceCommand;

class Command extends AbstractCommand
{
    /**
     * List of command actions
     *
     * @var array
     */
    private array $commands = [
        "clear" => ClearCommand::class,
        "seed:file" => SeederCommand::class,
        "seed:all" => SeederCommand::class,
        "migration:migrate" => MigrationCommand::class,
        "migration:rollback" => MigrationCommand::class,
        "migration:reset" => MigrationCommand::class,
        "add:controller" => GenerateControllerCommand::class,
        "add:configuration" => GenerateConfigurationCommand::class,
        "add:exception" => GenerateExceptionCommand::class,
        "add:middleware" => GenerateMiddlewareCommand::class,
        "add:migration" => GenerateMigrationCommand::class,
        "add:model" => GenerateModelCommand::class,
        "add:seeder" => GenerateSeederCommand::class,
        "add:service" => GenerateServiceCommand::class,
        "add:validation" => GenerateValidationCommand::class,
        "add:event" => GenerateAppEventCommand::class,
        "add:listener" => GenerateEventListenerCommand::class,
        "add:job" => GenerateJobCommand::class,
        "add:command" => GenerateConsoleCommand::class,
        "add:message" => GenerateMessagingCommand::class,
        "run:console" => ReplCommand::class,
        "run:server" => ServerCommand::class,
        "run:worker" => WorkerCommand::class,
        "flush:worker" => WorkerCommand::class,
        "generate:key" => GenerateKeyCommand::class,
        "generate:resource" => GenerateRouterResourceCommand::class,
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

        if (!preg_match('/^(migration|seed)/', $command)) {
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
