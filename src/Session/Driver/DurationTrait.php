<?php

declare(strict_types=1);

namespace Bow\Session\Driver;

trait DurationTrait
{
    /**
     * Create the timestamp
     *
     * @param int max_lifetime
     * @return string
     */
    private function createTimestamp(?int $max_lifetime = null): string
    {
        $lifetime = !is_null($max_lifetime) ? $max_lifetime : (config('session.lifetime') * 60);

        return date('Y-m-d H:i:s', time() + (int) $lifetime);
    }
}
