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
     * @param callable|null $cb
     * @return mixed
     */
    public function send($cb = null);
}