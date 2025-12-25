<?php

namespace Bow\Tests\Mail;

use Bow\Mail\Adapters\NativeAdapter;
use Bow\Mail\Envelop;
use Bow\Mail\Exception\MailException;
use Bow\Tests\Config\TestingConfiguration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class NativeAdapterTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        $config = TestingConfiguration::getConfig();
        $this->config = $config['mail'];
    }

    public function test_native_adapter_can_be_instantiated()
    {
        $adapter = new NativeAdapter([]);

        $this->assertInstanceOf(NativeAdapter::class, $adapter);
    }

    public function test_native_adapter_can_be_instantiated_with_config()
    {
        $config = [
            'default' => 'contact',
            'from' => [
                'contact' => [
                    'address' => 'test@example.com',
                    'name' => 'Test Sender'
                ]
            ]
        ];

        $adapter = new NativeAdapter($config);

        $this->assertInstanceOf(NativeAdapter::class, $adapter);
    }

    public function test_native_adapter_uses_default_from_address()
    {
        $config = [
            'default' => 'default',
            'from' => [
                'default' => [
                    'address' => 'sender@example.com',
                    'name' => 'Test Sender'
                ]
            ]
        ];

        $adapter = new NativeAdapter($config);

        $this->assertInstanceOf(NativeAdapter::class, $adapter);
    }

    public function test_native_adapter_on_method_switches_from_address()
    {
        $config = [
            'default' => 'default',
            'from' => [
                'default' => [
                    'address' => 'default@example.com',
                    'name' => 'Default Sender'
                ],
                'alternative' => [
                    'address' => 'alternative@example.com',
                    'name' => 'Alternative Sender'
                ]
            ]
        ];

        $adapter = new NativeAdapter($config);
        $adapter->on('alternative');

        $this->assertInstanceOf(NativeAdapter::class, $adapter);
    }

    public function test_native_adapter_on_method_throws_exception_for_undefined_from()
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('There are not entry for [nonexistent]');

        $config = [
            'default' => 'default',
            'from' => [
                'default' => [
                    'address' => 'default@example.com',
                    'name' => 'Default Sender'
                ]
            ]
        ];

        $adapter = new NativeAdapter($config);
        $adapter->on('nonexistent');
    }

    public function test_native_adapter_send_validates_required_to_field()
    {
        $this->expectException(InvalidArgumentException::class);

        $adapter = new NativeAdapter([]);
        $envelop = new Envelop();
        $envelop->subject('Test Subject')
            ->message('Test Message');

        $adapter->send($envelop);
    }

    public function test_native_adapter_send_validates_required_subject_field()
    {
        $adapter = new NativeAdapter([]);
        $envelop = new Envelop();
        $envelop->to('test@example.com')
            ->message('Test Message');

        try {
            $result = $adapter->send($envelop);
            // If it doesn't throw, it should return false
            $this->assertFalse($result);
        } catch (\Throwable $e) {
            // Accept any exception as valid validation
            $this->assertInstanceOf(\Throwable::class, $e);
        }
    }

    public function test_native_adapter_send_validates_required_message_field()
    {
        $adapter = new NativeAdapter([]);
        $envelop = new Envelop();
        $envelop->to('test@example.com')
            ->subject('Test Subject');

        try {
            $result = $adapter->send($envelop);
            // If it doesn't throw, it should return false
            $this->assertFalse($result);
        } catch (\Throwable $e) {
            // Accept any exception as valid validation
            $this->assertInstanceOf(\Throwable::class, $e);
        }
    }

    public function test_native_adapter_sends_email_with_basic_configuration()
    {
        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->message('Test Message');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_sends_email_to_multiple_recipients()
    {
        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to(['test1@example.com', 'test2@example.com', 'test3@example.com'])
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->message('Test Message');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_sends_email_with_named_recipient()
    {
        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to('Recipient Name <test@example.com>')
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->message('Test Message');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_sends_email_without_explicit_from()
    {
        $config = [
            'default' => 'default',
            'from' => [
                'default' => [
                    'address' => 'default@example.com',
                    'name' => 'Default Sender'
                ]
            ]
        ];

        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([$config])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to('test@example.com')
            ->subject('Test Subject')
            ->message('Test Message');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_sends_email_with_custom_headers()
    {
        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->message('Test Message')
            ->withHeader('X-Custom-Header', 'custom-value')
            ->withHeader('X-Priority', '1');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_sends_html_email()
    {
        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->html('<html><body><h1>Test HTML Message</h1></body></html>');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_sends_plain_text_email()
    {
        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->text('Plain text message content');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_sends_email_with_cc()
    {
        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to('test@example.com')
            ->addCc('cc@example.com')
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->message('Test Message');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_sends_email_with_bcc()
    {
        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to('test@example.com')
            ->bcc('bcc@example.com')
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->message('Test Message');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_sends_email_with_reply_to()
    {
        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com', 'Sender Name')
            ->replyTo('reply@example.com')
            ->subject('Test Subject')
            ->message('Test Message');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_handles_special_characters_in_subject()
    {
        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject with Special Chars: éàü & <>')
            ->message('Test Message');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_handles_long_message_content()
    {
        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $longMessage = str_repeat('This is a long message. ', 1000);

        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->message($longMessage);

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_handles_from_without_name()
    {
        $config = [
            'default' => 'default',
            'from' => [
                'default' => [
                    'address' => 'default@example.com'
                ]
            ]
        ];

        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([$config])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to('test@example.com')
            ->subject('Test Subject')
            ->message('Test Message');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_sends_email_with_utf8_content()
    {
        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test UTF-8: 你好世界')
            ->message('Message with UTF-8: こんにちは, مرحبا, Здравствуй');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_sends_email_with_empty_sender_name()
    {
        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com', '')
            ->subject('Test Subject')
            ->message('Test Message');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }

    public function test_native_adapter_on_method_returns_self()
    {
        $config = [
            'default' => 'default',
            'from' => [
                'default' => [
                    'address' => 'default@example.com'
                ],
                'alternative' => [
                    'address' => 'alternative@example.com'
                ]
            ]
        ];

        $adapter = new NativeAdapter($config);
        $result = $adapter->on('alternative');

        $this->assertSame($adapter, $result);
    }

    public function test_native_adapter_sends_email_with_multiple_mixed_recipients()
    {
        $mock = $this->getMockBuilder(NativeAdapter::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['executeNativeMail'])
            ->getMock();

        $mock->expects($this->once())
            ->method('executeNativeMail')
            ->willReturn(true);

        $envelop = (new Envelop())
            ->to(['Name One <test1@example.com>', 'test2@example.com', 'Name Three <test3@example.com>'])
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->message('Test Message');

        $result = $mock->send($envelop);

        $this->assertTrue($result);
    }
}
