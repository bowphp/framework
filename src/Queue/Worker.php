<?php

namespace Bow\Queue;

use Pheanstalk\Pheanstalk;

class Worker
{
    /**
     * Start the consumer
     *
     * @param string $queue_name
     * @param integer $retry
     * @return void
     */
    public function run($queue_name = "default", $retry = 60)
    {
        $queue = app("queue");

        $pheanstalk = Pheanstalk::create($queue["hostname"], $queue["port"], $queue["timeout"]);

        $pheanstalk->watch($queue_name);

        $job = $pheanstalk->reserve();
    }
}
