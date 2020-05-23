<?php

class MailServiceTest extends \PHPUnit\Framework\TestCase
{
    private $config;
    private $mail;

    public function setUp()
    {
        $this->config = require __DIR__.'/config/mail.php';
    }

    public function testConfigurationInstance()
    {
        $this->mail = \Bow\Mail\Mail::configure($this->config);
        $this->assertInstanceOf(\Bow\Mail\Contracts\MailDriverInterface::class, $this->mail);
    }

    public function testDefaultConfigurationMustBeSmtpDriver()
    {
        $this->mail = \Bow\Mail\Mail::configure($this->config);
        $this->assertInstanceOf(\Bow\Mail\Driver\SmtpDriver::class, $this->mail);
    }

    public function testConfigurationMustBeNativeDriver()
    {
        $this->config['driver'] = 'mail';
        $this->mail = \Bow\Mail\Mail::configure($this->config);
        $this->assertInstanceOf(\Bow\Mail\Driver\NativeDriver::class, $this->mail);
    }

    public function testSendMailWithRawContent()
    {
        $smtp = \Bow\Mail\Mail::configure($this->config);

        $response = \Bow\Mail\Mail::raw('bow@email.com', 'This is a test', 'The message content');

        $this->assertTrue($response);
    }


    public function testSendMailWithView()
    {
        $config = \Bow\Configuration\Loader::configure(__DIR__.'/config');
        \Bow\View\View::configure($config);

        $smtp = \Bow\Mail\Mail::configure($this->config);

        $response = \Bow\Mail\Mail::send('mail', [], function ($message) {
            $message->to('bow@email.dv');
        });

        $this->assertTrue($response);
    }
}
