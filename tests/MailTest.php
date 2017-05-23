<?php

if (getenv('DB_USER') == 'travis') {
    return;
}

use Bow\Mail\Mail;
use Bow\View\View;

class MailTest extends \PHPUnit\Framework\TestCase
{
    public function testSend()
    {
        View::configure(\Bow\Application\Configuration::configure([
            'application' => require realpath(__DIR__.'/config/application.php')
        ]));

        Mail::configure(require __DIR__.'/config/mail.php');

        $r = Mail::send('twig', ['name' => 'bow'], function (\Bow\Mail\Message $message) {
            $message->to('bow@root.com');
            $message->subject('Bonjour bow.');
        });

        $this->assertTrue($r);
    }
}