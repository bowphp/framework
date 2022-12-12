<?php

declare(strict_types=1);

namespace Bow\Support\CQRS;

use Bow\Support\CQRS\CQRSException;
use Bow\Support\CQRS\Query\QueryInterface;
use Bow\Support\CQRS\Command\CommandInterface;
use Bow\Support\CQRS\Query\QueryHandlerInterface;
use Bow\Support\CQRS\Command\CommandHandlerInterface;

final class Registration
{
    /**
     * Define the registra commands
     *
     * @var array
     */
    private static array $commands = [];

    /**
     * Define the registra queries
     *
     * @var array
     */
    private static array $queries = [];

    /**
     * Get the registra queries
     *
     * @param array $queries
     * @return void
     */
    public static function queries(array $queries): void
    {
        static::$queries = $queries;
    }

    /**
     * Get the registra command
     *
     * @param array $commands
     * @return void
     */
    public static function commands(array $commands): void
    {
        static::$commands = $commands;
    }

    /**
     * Get the registra command or query
     *
     * @param QueryInterface|CommandInterface $cq
     * @return QueryHandlerInterface|CommandHandlerInterface
     */
    public static function getHandler(
        QueryInterface|CommandInterface $cq
    ): QueryHandlerInterface|CommandHandlerInterface {
        $cq_class = get_class($cq);

        if ($cq instanceof QueryInterface) {
            $handler = static::$queries[$cq_class] ?? null;
        } else {
            $handler = static::$commands[$cq_class] ?? null;
        }

        if (!is_null($handler)) {
            return app($handler);
        }

        throw new CQRSException(
            sprintf(
                "The %s %s:class handler is not found on the CQ register",
                $cq instanceof QueryInterface ? 'query' : 'command',
                $cq_class
            )
        );
    }
}
