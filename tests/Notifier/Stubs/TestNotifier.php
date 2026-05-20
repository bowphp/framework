<?php

namespace Bow\Tests\Notifier\Stubs;

use Bow\Database\Barry\Model;
use Bow\Mail\Envelop;
use Bow\Notifier\Notifier;

class TestNotifier extends Notifier
{
    public function channels(Model $context): array
    {
        return ['mail', 'database', 'slack', 'sms', 'telegram'];
    }

    public function toMail(Model $context): ?Envelop
    {
        return (new Envelop())
            ->to('test@example.com')
            ->subject('Test Message')
            ->view('email');
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

    public function toSlack(Model $context): array
    {
        return [
            'webhook_url' => 'https://hooks.slack.com/services/test',
            'content' => [
                'text' => 'Test message for Slack'
            ]
        ];
    }

    public function toSms(Model $context): array
    {
        return [
            'to' => '+1234567890',
            'message' => 'Test SMS message'
        ];
    }

    public function toTelegram(Model $context): array
    {
        return [
            'chat_id' => '123456789',
            'message' => 'Test Telegram message',
            'parse_mode' => 'HTML'
        ];
    }
}
