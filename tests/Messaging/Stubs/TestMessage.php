<?php

namespace Bow\Tests\Messaging\Stubs;

use Bow\Database\Barry\Model;
use Bow\Mail\Message;
use Bow\Messaging\Messaging;

class TestMessage extends Messaging
{
    public function channels(Model $context): array
    {
        return ['mail', 'database'];
    }

    public function toMail(Model $context): Message
    {
        return (new Message())
            ->to('test@example.com')
            ->subject('Test Message')
            ->view('test-view');
    }

    public function toDatabase(Model $context): array
    {
        return [
            'type' => 'test_message',
            'data' => [
                'message' => 'Test message content'
            ]
        ];
    }
} 
