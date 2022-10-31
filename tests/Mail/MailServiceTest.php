<?php

namespace Bow\Tests\Mail;

use Bow\Configuration\Loader as ConfigurationLoader;
use Bow\Mail\Contracts\MailDriverInterface;
use Bow\Mail\Mail;
use Bow\Mail\Message;
use Bow\Tests\Config\TestingConfiguration;
use Bow\View\View;

function mail()
{
    return true;
}

class MailServiceTest extends \PHPUnit\Framework\TestCase
{
    private ConfigurationLoader $config;
    private MailDriverInterface $mail;

    protected function setUp(): void
    {
        $this->config = TestingConfiguration::getConfig();
    }

    public static function setUpBeforeClass(): void
    {
        if (function_exists('shell_exec') && !file_exists("/usr/sbin/sendmail")) {
            shell_exec("echo 'exit 0;' > /usr/sbin/sendmail && chmod +x /usr/sbin/sendmail");
        }
    }

    public function test_configuration_instance()
    {
        $mail = Mail::configure($this->config["mail"]);
        $this->assertInstanceOf(MailDriverInterface::class, $mail);
    }

    public function test_default_configuration_must_be_smtp_driver()
    {
        $mail = Mail::configure($this->config["mail"]);
        $this->assertInstanceOf(\Bow\Mail\Driver\SmtpDriver::class, $mail);
    }

    public function test_send_mail_with_raw_content_for_stmp_driver()
    {
        Mail::configure($this->config['mail']);
        $response = Mail::raw('bow@email.com', 'This is a test', 'The message content');

        $this->assertTrue($response);
    }

    public function test_send_mail_with_view_for_stmp_driver()
    {
        View::configure($this->config["view"]);
        Mail::configure($this->config["mail"]);

        $response = Mail::send('mail', ['name' => "papac"], function (Message $message) {
            $message->to('bow@bowphp.com');
        });

        $this->assertTrue($response);
    }

    public function test_configuration_must_be_native_driver()
    {
        $config = $this->config["mail"];
        $config['driver'] = 'mail';

        $mail_instance = Mail::configure($config);
        $this->assertInstanceOf(\Bow\Mail\Driver\NativeDriver::class, $mail_instance);
    }

    public function test_send_mail_with_raw_content_for_notive_driver()
    {
        $config = $this->config["mail"];
        $config['driver'] = 'mail';

        Mail::configure($config);
        $response = Mail::raw('bow@email.com', 'This is a test', 'The message content');

        $this->assertTrue($response);
    }

    public function test_send_mail_with_view_for_notive_driver()
    {
        View::configure($this->config["view"]);
        Mail::configure([...$this->config["mail"], "driver" => "mail"]);

        $response = Mail::send('mail', ['name' => "papac"], function (Message $message) {
            $message->to('bow@bowphp.com');
            $message->subject('test email');
        });

        $this->assertTrue($response);
    }
}
