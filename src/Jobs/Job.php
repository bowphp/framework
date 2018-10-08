<?php

namespace Bow\Jobs;

class Job
{
    /**
     * @var string
     */
    protected $queue = 'default';

    /**
     * The job handle
     */
    public function process()
    {
        //
    }
    
    /**
     * Dispatcher
     */
    private function dispatch()
    {
        //
    }
}
