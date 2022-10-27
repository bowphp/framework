<?php

namespace Bow\Tests\Mail;

use Bow\Tests\Config\TestingConfiguration;

class MailServiceTest extends \PHPUnit\Framework\TestCase
{
    private $config;
    private $mail;

    protected function setUp(): void
    {
        $this->config = TestingConfiguration::getConfig();
    }

    public function testConfigurationInstance()
    {
        $this->mail = \Bow\Mail\Mail::configure($this->config["mail"]);
        $this->assertInstanceOf(\Bow\Mail\Contracts\MailDriverInterface::class, $this->mail);
    }

    public function testDefaultConfigurationMustBeSmtpDriver()
    {
        $this->mail = \Bow\Mail\Mail::configure($this->config["mail"]);
        $this->assertInstanceOf(\Bow\Mail\Driver\SmtpDriver::class, $this->mail);
    }

    public function testConfigurationMustBeNativeDriver()
    {
        $config = $this->config["mail"];
        $config['driver'] = 'mail';
        $this->mail = \Bow\Mail\Mail::configure($config);
        $this->assertInstanceOf(\Bow\Mail\Driver\NativeDriver::class, $this->mail);
    }

    public function testSendMailWithRawContent()
    {
        $this->markTestSkipped('SMTP server did not accept MAIL FROM: <test@test.dev> with code [0]');
        $smtp = \Bow\Mail\Mail::configure($this->config["mail"]);

        $response = \Bow\Mail\Mail::raw('bow@email.com', 'This is a test', 'The message content');

        $this->assertTrue($response);
    }


    public function testSendMailWithView()
    {
        $this->markTestSkipped('SMTP server did not accept MAIL FROM: <test@test.dev> with code [0]');
        \Bow\View\View::configure($this->config);

        $smtp = \Bow\Mail\Mail::configure($this->config["mail"]);

        $response = \Bow\Mail\Mail::send('mail', [], function ($message) {
            $message->to('bow@email.dv');
        });

        $this->assertTrue($response);
    }
}
