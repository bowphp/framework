<?php

namespace Bow\Event;

trait Dispatchable
{
    /**
     * Dispatch the event with the given arguments.
     *
     * @return mixed
     */
    public static function dispatch(): mixed
    {
        return event(new static(...func_get_args()));
    }

    /**
     * Dispatch the event with the given arguments if the given truth test passes.
     *
     * @param bool $boolean
     * @param  mixed  ...$arguments
     * @return void
     */
    public static function dispatchIf(bool $boolean, ...$arguments): void
    {
        if ($boolean) {
            event(new static(...$arguments));
        }
    }

    /**
     * Dispatch the event with the given arguments unless the given truth test passes.
     *
     * @param bool $boolean
     * @param  mixed  ...$arguments
     * @return void
     */
    public static function dispatchUnless(bool $boolean, ...$arguments): void
    {
        if (! $boolean) {
            event(new static(...$arguments));
        }
    }
}
