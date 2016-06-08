<?php
namespace Bow\Mail;

/**
 * Interface Send
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Mail
 */
interface Send
{
    /**
     * send, envoie de mail.
     *
     * @param Message $message
     * @return mixed
     */
    public function send(Message $message);
}