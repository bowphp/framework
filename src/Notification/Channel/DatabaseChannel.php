<?php

namespace Bow\Notification\Channel;

use Bow\Database\Database;

class DatabaseChannel implements ChannelInterface
{
    public function __construct(
        private readonly array $database
    ) {
    }

    /**
     * Send the notification to database
     *
     * @return void
     */
    public function send(): void
    {
        Database::table('notifications')->insert($this->database);
    }
}
