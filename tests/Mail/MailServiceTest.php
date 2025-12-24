<?php

namespace Bow\Tests\Mail;

use Bow\Configuration\Loader as ConfigurationLoader;
use Bow\Mail\Contracts\MailAdapterInterface;
use Bow\Mail\Envelop;
use Bow\Mail\Exception\MailException;
use Bow\Mail\Mail;
use Bow\Tests\Config\TestingConfiguration;
use Bow\View\Exception\ViewException;
use Bow\View\View;
use InvalidArgumentException;

class MailServiceTest extends \PHPUnit\Framework\TestCase
{
    private ConfigurationLoader $config;

    /**
     * @var string Path to sendmail command
     */
    private static string $sendmail_command = '/usr/sbin/sendmail';

    protected function setUp(): void
    {
        $this->config = TestingConfiguration::getConfig();
        Mail::configure($this->config["mail"]);
        View::configure($this->config["view"]);
    }

    public function test_configuration_instance()
    {
        $mail = Mail::configure($this->config["mail"]);

        $this->assertInstanceOf(MailAdapterInterface::class, $mail);
    }

    public function test_default_configuration_must_be_smtp_driver()
    {
        $mail = Mail::configure($this->config["mail"]);

        $this->assertInstanceOf(\Bow\Mail\Adapters\SmtpAdapter::class, $mail);
    }

    public function test_configuration_must_be_native_driver()
    {
        $config = $this->config["mail"];
        $config['driver'] = 'mail';

        $mail_instance = Mail::configure($config);
        $this->assertInstanceOf(\Bow\Mail\Adapters\NativeAdapter::class, $mail_instance);
    }

    public function test_get_mail_instance()
    {
        Mail::configure($this->config["mail"]);
        $instance = Mail::getInstance();

        $this->assertInstanceOf(MailAdapterInterface::class, $instance);
    }

    /**
     * Note: SMTP tests may fail if SMTP server is not properly configured.
     * These tests require a working SMTP connection as configured in the test config.
     */
    public function test_send_mail_with_raw_content_for_smtp_driver()
    {
        Mail::configure($this->config['mail']);

        try {
            $response = Mail::raw('bow@email.com', 'This is a test', 'The message content');

            if ($response === false) {
                $this->markTestSkipped('SMTP server not accessible or configured');
            }

            $this->assertTrue($response);
        } catch (\Bow\Mail\Exception\SmtpException $e) {
            $this->markTestSkipped('SMTP server not configured: ' . $e->getMessage());
        }
    }

    public function test_send_mail_with_multiple_recipients_for_smtp_driver()
    {
        Mail::configure($this->config['mail']);

        try {
            $response = Mail::raw(['bow@email.com', 'test@example.com'], 'Multiple Recipients Test', 'This message goes to multiple recipients');
            if ($response === false) {
                $this->markTestSkipped('SMTP server not accessible or configured');
            }
            $this->assertTrue($response);
        } catch (\Bow\Mail\Exception\SmtpException $e) {
            $this->markTestSkipped('SMTP server not configured: ' . $e->getMessage());
        }
    }

    public function test_send_mail_with_custom_headers_for_smtp_driver()
    {
        Mail::configure($this->config['mail']);

        try {
            $response = Mail::raw('bow@email.com', 'Custom Headers Test', 'This message has custom headers', ['X-Custom-Header' => 'TestValue']);

            if ($response === false) {
                $this->markTestSkipped('SMTP server not accessible or configured');
            }

            $this->assertTrue($response);
        } catch (\Bow\Mail\Exception\SmtpException $e) {
            $this->markTestSkipped('SMTP server not configured: ' . $e->getMessage());
        }
    }

    public function test_send_mail_with_view_for_smtp_driver()
    {
        try {
            $response = Mail::send('mail', ['name' => "papac"], function (Envelop $envelop) {
                $envelop->to('bow@bowphp.com')
                    ->subject('Test Email');
            });

            if ($response === false) {
                $this->markTestSkipped('SMTP server not accessible or configured');
            }

            $this->assertTrue($response);
        } catch (\Bow\Mail\Exception\SmtpException $e) {
            $this->markTestSkipped('SMTP server not configured: ' . $e->getMessage());
        } catch (\Bow\Mail\Exception\MailException $e) {
            $this->markTestSkipped('Mail configuration error: ' . $e->getMessage());
        }
    }

    public function test_send_mail_with_view_and_subject_for_smtp_driver()
    {
        try {
            $response = Mail::send('mail', ['name' => "papac"], function (Envelop $envelop) {
                $envelop->to('bow@tests.com')
                        ->subject('Welcome Email')
                        ->from('noreply@tests.com', 'Bow Framework');
            });

            if ($response === false) {
                $this->markTestSkipped('SMTP server not accessible or configured');
            }

            $this->assertTrue($response);
        } catch (\Bow\Mail\Exception\SmtpException $e) {
            $this->markTestSkipped('SMTP server not configured: ' . $e->getMessage());
        }
    }

    public function test_send_mail_with_view_not_found_for_smtp_driver()
    {
        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('The view [mail_view_not_found.twig] does not exists.');

        Mail::send('mail_view_not_found', ['name' => "papac"], function (Envelop $envelop) {
            $envelop->to('bow@bowphp.com');
            $envelop->subject('test email');
        });
    }

    // ===== Native Driver Tests =====

    public function test_send_mail_with_raw_content_for_native_driver()
    {
        if (!file_exists('/usr/sbin/sendmail') && !file_exists(static::$sendmail_command)) {
            return $this->markTestSkipped('Test skipped because /usr/sbin/sendmail not found');
        }

        $config = $this->config["mail"];
        $config['driver'] = 'mail';
        $config['mail']['sendmail'] = static::$sendmail_command;

        Mail::configure($config);
        $response = Mail::raw('bow@email.com', 'This is a test', 'The message content');

        if ($response === false) {
            return $this->markTestSkipped('Native mail() function not functional on this system');
        }

        $this->assertTrue($response);
    }

    public function test_send_mail_with_view_for_native_driver()
    {
        $config = (array) $this->config["mail"];
        $config['driver'] = 'mail';

        Mail::configure($config);

        $response = Mail::send('mail', ['name' => "papac"], function (Envelop $envelop) {
            $envelop->to('bow@tests.com');
            $envelop->subject('test email');
        });

        if ($response === false) {
            return $this->markTestSkipped('Native mail() function not functional on this system');
        }

        $this->assertTrue($response);
    }

    public function test_send_mail_with_view_not_found_for_native_driver()
    {
        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('The view [mail_view_not_found.twig] does not exists.');

        Mail::send('mail_view_not_found', ['name' => "papac"], function (Envelop $envelop) {
            $envelop->to('bow@tests.com');
            $envelop->subject('test email');
        });
    }

    public function test_envelop_set_recipient()
    {
        $envelop = new Envelop();
        $envelop->to('test@example.com');

        $recipients = $envelop->getTo();
        $this->assertIsArray($recipients);
        $this->assertCount(1, $recipients);
    }

    public function test_envelop_set_multiple_recipients()
    {
        $envelop = new Envelop();
        $envelop->to(['test1@example.com', 'test2@example.com']);

        $recipients = $envelop->getTo();
        $this->assertCount(2, $recipients);
    }

    public function test_envelop_set_subject()
    {
        $envelop = new Envelop();
        $envelop->subject('Test Subject');

        $this->assertEquals('Test Subject', $envelop->getSubject());
    }

    public function test_envelop_set_message()
    {
        $envelop = new Envelop();
        $envelop->setMessage('Test message content');

        $this->assertEquals('Test message content', $envelop->getMessage());
    }

    public function test_envelop_set_from()
    {
        $envelop = new Envelop();
        $envelop->from('sender@example.com', 'Sender Name');

        $from = $envelop->getFrom();
        $this->assertStringContainsString('sender@example.com', $from);
        $this->assertStringContainsString('Sender Name', $from);
    }

    public function test_envelop_set_from_without_name()
    {
        $envelop = new Envelop();
        $envelop->from('sender@example.com');

        $this->assertEquals('sender@example.com', $envelop->getFrom());
    }

    public function test_envelop_set_html_content()
    {
        $envelop = new Envelop();
        $envelop->html('<h1>HTML Content</h1>');

        $this->assertEquals('<h1>HTML Content</h1>', $envelop->getMessage());
        $this->assertEquals('text/html', $envelop->getType());
    }

    public function test_envelop_set_text_content()
    {
        $envelop = new Envelop();
        $envelop->text('Plain text content');

        $this->assertEquals('Plain text content', $envelop->getMessage());
        $this->assertEquals('text/plain', $envelop->getType());
    }

    public function test_envelop_with_custom_header()
    {
        $envelop = new Envelop();
        $envelop->withHeader('X-Custom-Header', 'CustomValue');

        $headers = $envelop->getHeaders();
        $this->assertContains('X-Custom-Header: CustomValue', $headers);
    }

    public function test_envelop_invalid_email_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);

        $envelop = new Envelop();
        $envelop->to('invalid-email');
    }

    public function test_envelop_get_charset()
    {
        $envelop = new Envelop();
        $this->assertEquals('utf-8', $envelop->getCharset());
    }

    public function test_envelop_get_type()
    {
        $envelop = new Envelop();
        $this->assertEquals('text/html', $envelop->getType());
    }

    public function test_envelop_add_file_throws_exception_for_nonexistent_file()
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('file was not found');

        $envelop = new Envelop();
        $envelop->addFile('/path/to/nonexistent/file.pdf');
    }

    public function test_envelop_chain_methods()
    {
        $envelop = new Envelop();
        $result = $envelop->to('test@example.com')
                          ->subject('Chained Subject')
                          ->from('sender@example.com');

        $this->assertInstanceOf(Envelop::class, $result);
        $this->assertEquals('Chained Subject', $envelop->getSubject());
    }

    // ===== Edge Cases =====

    public function test_send_mail_with_empty_subject()
    {
        Mail::configure($this->config['mail']);

        try {
            $response = Mail::raw('bow@email.com', '', 'Message without subject');

            if ($response === false) {
                $this->markTestSkipped('SMTP server not accessible or configured');
            }

            $this->assertTrue($response);
        } catch (\Bow\Mail\Exception\SmtpException $e) {
            $this->markTestSkipped('SMTP server not configured: ' . $e->getMessage());
        }
    }

    public function test_envelop_with_named_email_format()
    {
        $envelop = new Envelop();
        $envelop->to('John Doe <john@example.com>');

        $recipients = $envelop->getTo();
        $this->assertCount(1, $recipients);
        $this->assertEquals('John Doe', $recipients[0][0]);
        $this->assertEquals('john@example.com', $recipients[0][1]);
    }

    public function test_envelop_compile_headers()
    {
        $envelop = new Envelop();
        $envelop->to('test@example.com')
                ->subject('Test')
                ->from('sender@example.com');

        $headers = $envelop->compileHeaders();
        $this->assertIsString($headers);
        $this->assertStringContainsString('Mime-Version', $headers);
    }

    public function test_envelop_set_message_with_type()
    {
        $envelop = new Envelop();
        $envelop->setMessage('Custom message', 'text/plain');

        $this->assertEquals('text/plain', $envelop->getType());
        $this->assertEquals('Custom message', $envelop->getMessage());
    }

    public function test_mail_send_with_callback_only()
    {
        try {
            $response = Mail::send('mail', function (Envelop $envelop) {
                $envelop->to('bow@bowphp.com')
                        ->subject('Callback Only Test');
            });

            if ($response === false) {
                $this->markTestSkipped('SMTP server not accessible or configured');
            }

            $this->assertTrue($response);
        } catch (\Bow\Mail\Exception\SmtpException $e) {
            $this->markTestSkipped('SMTP server not configured: ' . $e->getMessage());
        }
    }

    public function test_envelop_multiple_calls_to_same_method()
    {
        $envelop = new Envelop();
        $envelop->to('first@example.com');
        $envelop->to('second@example.com');

        $recipients = $envelop->getTo();
        $this->assertCount(2, $recipients);
    }
}
