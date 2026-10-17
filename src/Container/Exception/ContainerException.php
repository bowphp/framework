<?php

declare(strict_types=1);

namespace Bow\Container\Exception;

use RuntimeException;

/**
 * Raised when the container cannot build a dependency.
 */
class ContainerException extends RuntimeException
{
    /**
     * The key asked of the container could not be turned into an instance
     * because it is an interface or an abstract class with no binding.
     *
     * @param  string $key
     * @return ContainerException
     */
    public static function notInstantiable(string $key): ContainerException
    {
        $kind = interface_exists($key) ? 'interface' : 'abstract class';

        return new static(sprintf(
            'Cannot resolve [%s]: it is an %s and no binding is registered for it. '
            . 'Bind a concrete implementation, for example app()->bind(%s::class, Concrete::class).',
            $key,
            $kind,
            $key
        ));
    }
}
