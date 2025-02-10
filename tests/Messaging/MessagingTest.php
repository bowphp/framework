<?php

namespace Bow\Tests\Messaging;

use Bow\Database\Barry\Model;
use Bow\Mail\Envelop;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Messaging\Stubs\TestMessage;
use Bow\Tests\Messaging\Stubs\TestNotifiableModel;
use Bow\View\View;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MessagingTest extends TestCase
{
    private MockObject|Model $context;
    private MockObject|TestMessage $message;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Initialize queue connection
        $config = TestingConfiguration::getConfig();

        View::configure($config["view"]);
    }

    public function test_can_send_message_synchronously(): void
    {
        $this->message->expects($this->once())
            ->method('process')
            ->with($this->context);

        $this->context->sendMessage($this->message);
    }

    public function test_message_sends_to_correct_channels(): void
    {
        $context = new TestNotifiableModel();
        $message = new TestMessage();

        $channels = $message->channels($context);

        $this->assertEquals(['mail', 'database', 'slack', 'sms', 'telegram'], $channels);
    }

    public function test_message_can_send_to_mail(): void
    {
        $context = new TestNotifiableModel();
        $message = new TestMessage();

        $mailMessage = $message->toMail($context);
        [$email] = $mailMessage->getTo();

        $this->assertInstanceOf(Envelop::class, $mailMessage);
        $this->assertEquals('test@example.com', $email[1]);
        $this->assertEquals('Test Message', $mailMessage->getSubject());
    }

    public function test_message_can_send_to_database(): void
    {
        $context = new TestNotifiableModel();
        $message = new TestMessage();

        $data = $message->toDatabase($context);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals('test_message', $data['type']);
        $this->assertEquals('Test message content', $data['data']['message']);
    }

    public function test_message_can_send_to_slack(): void
    {
        $context = new TestNotifiableModel();
        $message = new TestMessage();

        $data = $message->toSlack($context);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('webhook_url', $data);
        $this->assertArrayHasKey('content', $data);
        $this->assertEquals('https://hooks.slack.com/services/test', $data['webhook_url']);
        $this->assertEquals('Test message for Slack', $data['content']['text']);
    }

    public function test_message_can_send_to_sms(): void
    {
        $context = new TestNotifiableModel();
        $message = new TestMessage();

        $data = $message->toSms($context);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('to', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('+1234567890', $data['to']);
        $this->assertEquals('Test SMS message', $data['message']);
    }

    public function test_message_can_send_to_telegram(): void
    {
        $context = new TestNotifiableModel();
        $message = new TestMessage();

        $data = $message->toTelegram($context);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('chat_id', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('parse_mode', $data);
        $this->assertEquals('123456789', $data['chat_id']);
        $this->assertEquals('Test Telegram message', $data['message']);
        $this->assertEquals('HTML', $data['parse_mode']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = new TestNotifiableModel();
        $this->message = $this->createMock(TestMessage::class);
    }
}
