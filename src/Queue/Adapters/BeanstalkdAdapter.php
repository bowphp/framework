<?php

namespace Bow\Queue\Adapters;

use Pheanstalk\Pheanstalk;

class BeanstalkdAdapter implements QueueAdapter
{
    public function configure()
    {

    }

    public function run()
    {
        $pheanstalk = Pheanstalk::create($queue["hostname"], $queue["port"], $queue["timeout"]);

        $pheanstalk->watch($queue_name);

        $job = $pheanstalk->reserve();
    }
}