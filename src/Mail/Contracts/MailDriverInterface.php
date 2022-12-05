<?php

declare(strict_types=1);

namespace Bow\Mail\Contracts;

use Bow\Mail\Message;

interface MailDriverInterface
{
    /**
     * Send mail by any driver
     *
     * @param Message $message
     * @return bool
     */
    public function send(Message $message): bool;
}
