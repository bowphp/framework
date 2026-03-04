<?php

namespace Bow\Tests\Scheduler\Stubs;

use Bow\Queue\QueueTask;

class TestQueueTaskStub extends QueueTask
{
    /**
     * Track if process was called
     *
     * @var bool
     */
    public static bool $processed = false;

    /**
     * Track the data passed to the task
     *
     * @var mixed
     */
    public static $processedData = null;

    /**
     * The data to process
     *
     * @var mixed
     */
    protected $data;

    /**
     * Create a new task instance
     *
     * @param  mixed $data
     * @return void
     */
    public function __construct($data = null)
    {
        $this->data = $data;
    }

    /**
     * Process the task
     *
     * @return void
     */
    public function process(): void
    {
        static::$processed = true;
        static::$processedData = $this->data;
    }

    /**
     * Reset the static state
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$processed = false;
        static::$processedData = null;
    }
}
