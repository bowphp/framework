<?php

namespace Bow\Queue\Adapters;

use Pheanstalk\Pheanstalk;

class BeanstalkdAdapter implements QueueAdapter
{
    /**
     * Define the instance Pheanstalk
     * 
     * @var Pheanstalk
     */
    private $pheanstalk;

    /**
     * Determine the default watch name
     * 
     * @var string
     */
    private $watch = "default";

    /**
     * Configure Beanstalkd driver
     * 
     * @param array $queue
     * @return mixed
     */
    public function configure(array $queue)
    {
        $this->pheanstalk = Pheanstalk::create($queue["hostname"], $queue["port"], $queue["timeout"]);
    }

    /**
     * Get connexion
     * 
     * @param string $name
     * 
     * @return Pheanstalk
     */
    public function setWatch(string $name)
    {
        $this->watch = $name;
    }

    /**
     * Run the worker
     * 
     * @return mixed
     */
    public function run()
    {
        // we want jobs from 'testtube' only.
        $this->pheanstalk->watch($this->watch);

        // this hangs until a Job is produced.
        $job = $this->pheanstalk->reserve();

        try {
            $jobPayload = $job->getData();
            // do work.

            sleep(2);
            // If it's going to take a long time, periodically
            // tell beanstalk we're alive to stop it rescheduling the job.
            $pheanstalk->touch($job);
            sleep(2);

            // eventually we're done, delete job.
            $pheanstalk->delete($job);
        }
        catch(\Exception $e) {
            // handle exception.
            // and let some other worker retry.
            $pheanstalk->release($job); 
        }
    }
}