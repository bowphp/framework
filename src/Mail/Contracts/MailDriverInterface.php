<?php

namespace Bow\Mail\Contracts;

use Bow\Mail\Message;

interface MailDriverInterface
{
    /**
     * Send mail by any driver
     *
     * @param Message $message
     * @return mixed
     */
    public function send(Message $message);
}
