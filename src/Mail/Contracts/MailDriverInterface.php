<?php

namespace Bow\Mail\Contracts;

use Bow\Mail\Message;

abstract class MailDriverInterface
{
    /**
     * Send mail by any driver
     *
     * @param Message $message
     * @return mixed
     */
    abstract public function send(Message $message);
}
