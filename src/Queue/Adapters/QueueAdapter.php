<?php

namespace Bow\Queue\Adapters;

interface QueueAdapter
{
	/**
	 * Make adapter configuration
	 * 
	 * @param array $config
	 */
	public function configure(array $config);

	/**
	 * Watch the the queue name
	 * 
	 * @param string $queue_name
	 */
	public function setWatch(string $queue_name);

	/**
	 * Start the server worker
	 */
	public function run();
}