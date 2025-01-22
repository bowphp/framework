<?php

declare(strict_types=1);

namespace Bow\Console;

use Bow\Console\Command\AppEventCommand;
use Bow\Console\Command\ClearCommand;
use Bow\Console\Command\ConfigurationCommand;
use Bow\Console\Command\ConsoleCommand;
use Bow\Console\Command\ControllerCommand;
use Bow\Console\Command\EventListenerCommand;
use Bow\Console\Command\ExceptionCommand;
use Bow\Console\Command\GenerateCacheCommand;
use Bow\Console\Command\GenerateKeyCommand;
use Bow\Console\Command\GenerateQueueCommand;
use Bow\Console\Command\GenerateResourceControllerCommand;
use Bow\Console\Command\GenerateSessionCommand;
use Bow\Console\Command\MessagingCommand;
use Bow\Console\Command\MiddlewareCommand;
use Bow\Console\Command\MigrationCommand;
use Bow\Console\Command\ModelCommand;
use Bow\Console\Command\ProducerCommand;
use Bow\Console\Command\ReplCommand;
use Bow\Console\Command\SeederCommand;
use Bow\Console\Command\ServerCommand;
use Bow\Console\Command\ServiceCommand;
use Bow\Console\Command\ValidationCommand;
use Bow\Console\Command\WorkerCommand;
use Bow\Support\Str;
use ErrorException;

class Command extends AbstractCommand
{
    /**
     * List of command actions
     *
     * @var array
     */
    private array $command = [
        "clear" => ClearCommand::class,
        "migration" => MigrationCommand::class,
        "seeder" => SeederCommand::class,
        "add" => [
            "controller" => ControllerCommand::class,
            "configuration" => ConfigurationCommand::class,
            "exception" => ExceptionCommand::class,
            "middleware" => MiddlewareCommand::class,
            "migration" => MigrationCommand::class,
            "model" => ModelCommand::class,
            "seeder" => SeederCommand::class,
            "service" => ServiceCommand::class,
            "validation" => ValidationCommand::class,
            "event" => AppEventCommand::class,
            "listener" => EventListenerCommand::class,
            "producer" => ProducerCommand::class,
            "command" => ConsoleCommand::class,
            "messaging" => MessagingCommand::class,
        ],
        "generator" => [
            "key" => GenerateKeyCommand::class,
            "resource" => GenerateResourceControllerCommand::class,
            "session-table" => GenerateSessionCommand::class,
            "queue-table" => GenerateQueueCommand::class,
            "cache-table" => GenerateCacheCommand::class,
            "notification-table" => GenerateCacheCommand::class,
        ],
        "runner" => [
            "console" => ReplCommand::class,
            "server" => ServerCommand::class,
            "worker" => WorkerCommand::class,
        ],
        "flush" => [
            "worker" => WorkerCommand::class,
        ],
    ];

    /**
     * The call command
     *
     * @param string $action
     * @param string $command
     * @param array $rest
     * @return mixed
     * @throws ErrorException
     */
    public function call(string $command, string $action, ...$rest): mixed
    {
        $classes = $this->command[$command] ?? null;

        if (is_null($classes)) {
            $this->throwFailsCommand("The command $command not found !");
        }

        if ($command == "add" || $command == "generator") {
            $method = "generate";
        } elseif ($command == "runner") {
            $method = "run";
        } else {
            $method = Str::camel($action);
        }

        if (is_array($classes)) {
            $class = $classes[$action];
        } else {
            $class = $classes;
        }

        $instance = new $class($this->setting, $this->arg);

        if (method_exists($instance, $method)) {
            return call_user_func_array([$instance, $method], $rest);
        }

        return null;
    }
}
