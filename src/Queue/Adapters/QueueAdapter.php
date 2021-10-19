<?php

namespace Bow\Queue\Adapters;

use Bow\Queue\ProducerService;

abstract class QueueAdapter
{
    /**
     * Create producer serialization
     *
     * @param ProducerService $producer
     * @return string
     */
    public function serializeProducer(ProducerService $producer)
    {
        return serialize($producer);
    }

	/**
	 * Make adapter configuration
	 * 
	 * @param array $config
	 */
    abstract public function configure(array $config);

    /**
     * Watch the the queue name
     * 
     * @param string $queue
     */
    abstract public function setWatch(string $queue);

    /**
     * Set the retry value
     * 
     * @param int $retry
     */
    abstract public function setRetry(int $retry);

    /**
     * Push new producer
     * 
     * @param ProducerService $producer
     */
    abstract public function push(ProducerService $producer);

    /**
     * Get the queue size
     * 
     * @param string $queue
     */
    abstract public function size(string $queue);

    /**
     * Delete a message from the queue.
     *
     * @param  string  $queue
     * @param  string|int  $id
     * @return void
     */
    abstract public function deleteJob($queue, $id);

    /**
     * Get the queue or return the default.
     *
     * @param  string|null $queue
     * @return string
     */
    abstract public function getQueue(string $queue = null);

    /**
     * Start the worker server
     * 
     * @param string|null $queue
     */
    abstract public function run(string $queue = null);
}
