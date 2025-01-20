<?php

declare(strict_types=1);

namespace Bow\Console;

use Bow\Support\Str;

class Command extends AbstractCommand
{
    /**
     * List of command actions
     *
     * @var array
     */
    private array $command = [
        "clear" => \Bow\Console\Command\ClearCommand::class,
        "migration" => \Bow\Console\Command\MigrationCommand::class,
        "seeder" => \Bow\Console\Command\SeederCommand::class,
        "add" => [
            "controller" => \Bow\Console\Command\ControllerCommand::class,
            "configuration" => \Bow\Console\Command\ConfigurationCommand::class,
            "exception" => \Bow\Console\Command\ExceptionCommand::class,
            "middleware" => \Bow\Console\Command\MiddlewareCommand::class,
            "migration" => \Bow\Console\Command\MigrationCommand::class,
            "model" => \Bow\Console\Command\ModelCommand::class,
            "seeder" => \Bow\Console\Command\SeederCommand::class,
            "service" => \Bow\Console\Command\ServiceCommand::class,
            "validation" => \Bow\Console\Command\ValidationCommand::class,
            "event" => \Bow\Console\Command\AppEventCommand::class,
            "listener" => \Bow\Console\Command\EventListenerCommand::class,
            "producer" => \Bow\Console\Command\ProducerCommand::class,
            "command" => \Bow\Console\Command\ConsoleCommand::class,
            "messaging" => \Bow\Console\Command\MessagingCommand::class,
        ],
        "generator" => [
            "key" => \Bow\Console\Command\GenerateKeyCommand::class,
            "resource" => \Bow\Console\Command\GenerateResourceControllerCommand::class,
            "session-table" => \Bow\Console\Command\GenerateSessionCommand::class,
            "queue-table" => \Bow\Console\Command\GenerateQueueCommand::class,
            "cache-table" => \Bow\Console\Command\GenerateCacheCommand::class,
            "notification-table" => \Bow\Console\Command\GenerateCacheCommand::class,
        ],
        "runner" => [
            "console" => \Bow\Console\Command\ReplCommand::class,
            "server" => \Bow\Console\Command\ServerCommand::class,
            "worker" => \Bow\Console\Command\WorkerCommand::class,
        ],
        "flush" => [
            "worker" => \Bow\Console\Command\WorkerCommand::class,
        ],
    ];

    /**
     * The call command
     *
     * @param string $action
     * @param string $command
     * @param array $rest
     * @return mixed
     * @throws \ErrorException
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
