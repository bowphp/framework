<?php

namespace Bow\Console;

use Bow\Console\Command\AbstractCommand;

class Command extends AbstractCommand
{
    /**
     * List of command actions
     *
     * @var array
     */
    private $actions = [
        'configuration' => \Bow\Console\Command\ConfigurationCommand::class,
        'server' => \Bow\Console\Command\ServerCommand::class,
        'console' => \Bow\Console\Command\ReplCommand::class,
        'migration' => \Bow\Console\Command\MigrationCommand::class,
        'controller' => \Bow\Console\Command\ControllerCommand::class,
        'resource' => \Bow\Console\Command\ResourceControllerCommand::class,
        'middleware' => \Bow\Console\Command\MiddlewareCommand::class,
        'model' => \Bow\Console\Command\ModelCommand::class,
        'seeder' => \Bow\Console\Command\SeederCommand::class,
        'validator' => \Bow\Console\Command\ValidatorCommand::class,
        'key' => \Bow\Console\Command\GenerateKeyCommand::class,
        'session' => \Bow\Console\Command\GenerateSessionCommand::class,
        'clear' => \Bow\Console\Command\ClearCommand::class,
        'service' => \Bow\Console\Command\ServiceCommand::class,
        'exception' => \Bow\Console\Command\ExceptionCommand::class,
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
        $class = $this->actions[$action];

        $instance = new $class($this->setting, $this->arg);

        if (method_exists($instance, $command)) {
            return call_user_func_array([$instance, $command], $rest);
        }
    }
}
