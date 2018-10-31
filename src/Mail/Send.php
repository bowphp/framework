<?php

namespace Bow\Mail;

interface Send
{
    /**
     * Send, envoie de mail.
     *
     * @param Message $message
     * @return mixed
     */
    public function send(Message $message);
}
