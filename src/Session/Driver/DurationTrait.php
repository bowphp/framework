<?php

namespace Bow\Session\Driver;

trait DurationTrait
{
    /**
     * Create the timestamp
     *
     * @return string
     */
    private function createTimestamp()
    {
        return date('Y-m-d H:i:s', time() + (int) (config('session.lifetime') * 60));
    }
}
