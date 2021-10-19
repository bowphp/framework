<?php

namespace Bow\Console;

use Bow\Console\Command\AbstractCommand;
use Bow\Support\Str;

class Command extends AbstractCommand
{
    /**
     * List of command actions
     *
     * @var array
     */
    private $command = [
        "migration" => \Bow\Console\Command\MigrationCommand::class,
        "clear" => \Bow\Console\Command\ClearCommand::class,
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
            "event" => \Bow\Console\Command\EventCommand::class,
            "producer" => \Bow\Console\Command\ProducerCommand::class,
        ],
        "runner" => [
            "console" => \Bow\Console\Command\ReplCommand::class,
            "server" => \Bow\Console\Command\ServerCommand::class,
            "worker" => \Bow\Console\Command\WorkerCommand::class,
        ],
        "generator" => [
            "key" => \Bow\Console\Command\GenerateKeyCommand::class,
            "resource" => \Bow\Console\Command\GenerateResourceControllerCommand::class,
            "session" => \Bow\Console\Command\GenerateSessionCommand::class,
        ],
    ];

    /**
     * The call command
     *
     * @param string $action
     * @param string $command
     * @param array $rest
     *
     * @return mixed
     */
    public function call($command, $action, ...$rest)
    {
        $class = $this->command[$command];

        if ($command == "add" || $command == "generator") {
            $method = "generate";
        } elseif ($command == "runner") {
            $method = "run";
        } else {
            $method = Str::camel($action);
        }

        if (is_array($class)) {
            $class = $class[$action];
        }

        $instance = new $class($this->setting, $this->arg);

        if (method_exists($instance, $method)) {
            return call_user_func_array([$instance, $method], $rest);
        }
    }
}
