<?php

namespace Bow\Tests\Messaging;

use Bow\View\View;
use Bow\Mail\Envelop;
use Bow\Messaging\Messaging;
use Bow\Database\Database;
use Bow\Database\Barry\Model;
use Bow\Mail\Mail;
use PHPUnit\Framework\TestCase;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Messaging\Stubs\TestMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Bow\Tests\Messaging\Stubs\TestNotifiableModel;

class MessagingTest extends TestCase
{
    private TestNotifiableModel $context;
    private MockObject|TestMessage $message;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $config = TestingConfiguration::getConfig();

        Database::configure($config["database"]);
        Mail::configure($config["mail"]);
        View::configure($config["view"]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = new TestNotifiableModel();
        $this->message = $this->createMock(TestMessage::class);
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
        $message = new TestMessage();
        $channels = $message->channels($this->context);

        $this->assertIsArray($channels);
        $this->assertCount(5, $channels);
        $this->assertEquals(['mail', 'database', 'slack', 'sms', 'telegram'], $channels);
    }

    public function test_message_can_send_to_mail(): void
    {
        $message = new TestMessage();
        $mailMessage = $message->toMail($this->context);

        $this->assertInstanceOf(Envelop::class, $mailMessage);
        
        [$email] = $mailMessage->getTo();
        $this->assertEquals('test@example.com', $email[1]);
        $this->assertEquals('Test Message', $mailMessage->getSubject());
    }

    public function test_message_can_send_to_database(): void
    {
        $message = new TestMessage();
        $data = $message->toDatabase($this->context);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals('test_message', $data['type']);
        $this->assertIsArray($data['data']);
        $this->assertArrayHasKey('message', $data['data']);
        $this->assertEquals('Test message content', $data['data']['message']);
    }

    public function test_message_can_send_to_slack(): void
    {
        $message = new TestMessage();
        $data = $message->toSlack($this->context);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('webhook_url', $data);
        $this->assertArrayHasKey('content', $data);
        $this->assertEquals('https://hooks.slack.com/services/test', $data['webhook_url']);
        $this->assertIsArray($data['content']);
        $this->assertArrayHasKey('text', $data['content']);
        $this->assertEquals('Test message for Slack', $data['content']['text']);
    }

    public function test_message_can_send_to_sms(): void
    {
        $message = new TestMessage();
        $data = $message->toSms($this->context);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('to', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('+1234567890', $data['to']);
        $this->assertEquals('Test SMS message', $data['message']);
    }

    public function test_message_can_send_to_telegram(): void
    {
        $message = new TestMessage();
        $data = $message->toTelegram($this->context);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('chat_id', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('parse_mode', $data);
        $this->assertEquals('123456789', $data['chat_id']);
        $this->assertEquals('Test Telegram message', $data['message']);
        $this->assertEquals('HTML', $data['parse_mode']);
    }

    public function test_process_calls_all_channels(): void
    {
        $message = $this->getMockBuilder(TestMessage::class)
            ->onlyMethods(['channels', 'toMail', 'toDatabase'])
            ->getMock();

        $message->expects($this->once())
            ->method('channels')
            ->with($this->context)
            ->willReturn(['mail', 'database']);

        $message->expects($this->once())
            ->method('toMail')
            ->with($this->context)
            ->willReturn((new Envelop())->to('test@example.com')->subject('Test'));

        $message->expects($this->once())
            ->method('toDatabase')
            ->with($this->context)
            ->willReturn(['type' => 'test', 'data' => []]);

        $message->process($this->context);
    }

    public function test_message_returns_empty_array_for_unconfigured_channels(): void
    {
        $messaging = new class extends Messaging {
            public function channels(Model $context): array
            {
                return [];
            }
        };

        $this->assertEquals([], $messaging->toDatabase($this->context));
        $this->assertEquals([], $messaging->toSms($this->context));
        $this->assertEquals([], $messaging->toSlack($this->context));
        $this->assertEquals([], $messaging->toTelegram($this->context));
        $this->assertNull($messaging->toMail($this->context));
    }

    public function test_can_push_custom_channels(): void
    {
        $customChannels = [
            'custom' => \stdClass::class,
        ];

        $result = Messaging::pushChannels($customChannels);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('custom', $result);
        $this->assertArrayHasKey('mail', $result);
        $this->assertArrayHasKey('database', $result);
    }

    public function test_message_process_skips_invalid_channels(): void
    {
        $message = $this->getMockBuilder(TestMessage::class)
            ->onlyMethods(['channels', 'toMail'])
            ->getMock();
        
        $message->expects($this->once())
            ->method('channels')
            ->with($this->context)
            ->willReturn(['invalid_channel', 'mail']);

        $message->expects($this->once())
            ->method('toMail')
            ->with($this->context)
            ->willReturn((new Envelop())->to('test@example.com')->subject('Test'));

        // Should not throw exception for invalid channel
        $message->process($this->context);
        
        $this->assertTrue(true);
    }

    public function test_mail_message_returns_correct_envelop_instance(): void
    {
        $message = new TestMessage();
        $mailMessage = $message->toMail($this->context);

        $this->assertInstanceOf(Envelop::class, $mailMessage);
        $this->assertNotNull($mailMessage->getSubject());
        $this->assertNotEmpty($mailMessage->getTo());
    }

    public function test_database_message_has_required_structure(): void
    {
        $message = new TestMessage();
        $data = $message->toDatabase($this->context);

        // Verify required structure
        $this->assertIsArray($data);
        $this->assertArrayHasKey('type', $data);
        $this->assertIsString($data['type']);
        $this->assertNotEmpty($data['type']);
    }

    public function test_slack_message_has_valid_webhook_url(): void
    {
        $message = new TestMessage();
        $data = $message->toSlack($this->context);

        $this->assertArrayHasKey('webhook_url', $data);
        $this->assertIsString($data['webhook_url']);
        $this->assertStringStartsWith('https://', $data['webhook_url']);
    }

    public function test_sms_message_has_valid_phone_number(): void
    {
        $message = new TestMessage();
        $data = $message->toSms($this->context);

        $this->assertArrayHasKey('to', $data);
        $this->assertIsString($data['to']);
        $this->assertStringStartsWith('+', $data['to']);
    }

    public function test_telegram_message_has_valid_parse_mode(): void
    {
        $message = new TestMessage();
        $data = $message->toTelegram($this->context);

        $this->assertArrayHasKey('parse_mode', $data);
        $this->assertContains($data['parse_mode'], ['HTML', 'Markdown', 'MarkdownV2']);
    }

    public function test_context_has_send_message_trait(): void
    {
        $this->assertTrue(
            method_exists($this->context, 'sendMessage'),
            'Context should have sendMessage method from CanSendMessage trait'
        );

        $this->assertTrue(
            method_exists($this->context, 'setMessageQueue'),
            'Context should have setMessageQueue method from CanSendMessage trait'
        );

        $this->assertTrue(
            method_exists($this->context, 'sendMessageQueueOn'),
            'Context should have sendMessageQueueOn method from CanSendMessage trait'
        );
    }

    public function test_channels_method_is_abstract_and_must_be_implemented(): void
    {
        $message = new TestMessage();
        
        $this->assertTrue(
            method_exists($message, 'channels'),
            'Message class must implement channels method'
        );
        
        $channels = $message->channels($this->context);
        $this->assertIsArray($channels);
    }
}
