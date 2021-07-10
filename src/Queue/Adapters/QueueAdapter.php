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
	 * Start the server worker
	 */
	public function run();
}