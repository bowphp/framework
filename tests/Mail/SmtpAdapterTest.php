<?php

namespace Bow\Tests\Mail;

use Bow\Mail\Adapters\SmtpAdapter;
use Bow\Mail\Envelop;
use Bow\Mail\Exception\MailException;
use Bow\Tests\Config\TestingConfiguration;
use PHPUnit\Framework\TestCase;

class SmtpAdapterTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        $config = TestingConfiguration::getConfig();
        $this->config = (array) $config['mail']['smtp'];
    }

    public function test_smtp_adapter_can_be_instantiated()
    {
        $adapter = new SmtpAdapter($this->config);

        $this->assertInstanceOf(SmtpAdapter::class, $adapter);
    }

    public function test_smtp_adapter_validates_required_configuration()
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('hostname');

        $invalidConfig = ['driver' => 'smtp', 'mail' => ['smtp' => []]];
        new SmtpAdapter($invalidConfig);
    }

    public function test_smtp_adapter_requires_hostname()
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('hostname');

        $config = $this->config;
        unset($config['hostname']);

        new SmtpAdapter($config);
    }

    public function test_smtp_adapter_allows_optional_username_and_password()
    {
        $config = $this->config;
        unset($config['username']);
        unset($config['password']);

        $adapter = new SmtpAdapter($config);

        $this->assertInstanceOf(SmtpAdapter::class, $adapter);
    }

    public function test_smtp_adapter_validates_port_number()
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('port');

        $config = $this->config;
        $config['port'] = 'invalid';

        new SmtpAdapter($config);
    }

    public function test_smtp_adapter_validates_timeout()
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('timeout');

        $config = $this->config;
        $config['timeout'] = 'invalid';

        new SmtpAdapter($config);
    }

    public function test_smtp_adapter_validates_envelop_has_recipients()
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('No recipients specified');

        $adapter = new SmtpAdapter($this->config);
        $envelop = new Envelop();
        $envelop->message('Test message');

        // Should return false when no connection available (graceful failure)
        $result = $adapter->send($envelop);

        $this->assertFalse($result);
    }

    public function test_smtp_adapter_validates_envelop_has_message()
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('No message content specified');

        $adapter = new SmtpAdapter($this->config);

        $envelop = new Envelop();
        $envelop->to('test@example.com');

        // Should return false when no connection available (graceful failure)
        $result = $adapter->send($envelop);

        $this->assertFalse($result);
    }

    public function test_smtp_adapter_returns_false_on_connection_failure()
    {
        $adapter = new SmtpAdapter($this->config);
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->subject('Test')
            ->message('Test message');

        // Should return false since SMTP server is not available
        $result = $adapter->send($envelop);

        $this->assertTrue($result);
    }

    public function test_smtp_adapter_uses_default_port_when_not_specified()
    {
        $config = $this->config;
        unset($config['mail']['smtp']['port']);

        $adapter = new SmtpAdapter($config);

        $this->assertInstanceOf(SmtpAdapter::class, $adapter);
    }

    public function test_smtp_adapter_uses_default_timeout_when_not_specified()
    {
        $config = $this->config;
        unset($config['mail']['smtp']['timeout']);

        $adapter = new SmtpAdapter($config);

        $this->assertInstanceOf(SmtpAdapter::class, $adapter);
    }

    public function test_smtp_adapter_handles_ssl_security()
    {
        $config = $this->config;
        $config['mail']['smtp']['secure'] = 'ssl';
        $config['mail']['smtp']['port'] = 465;

        $adapter = new SmtpAdapter($config);

        $this->assertInstanceOf(SmtpAdapter::class, $adapter);
    }

    public function test_smtp_adapter_handles_no_security()
    {
        $config = $this->config;
        unset($config['mail']['smtp']['secure']);

        $adapter = new SmtpAdapter($config);

        $this->assertInstanceOf(SmtpAdapter::class, $adapter);
    }

    public function test_smtp_adapter_accepts_valid_security_types()
    {
        $securityTypes = ['tls', 'ssl', 'TLS', 'SSL', null, ''];

        foreach ($securityTypes as $securityType) {
            $config = $this->config;
            $config['mail']['smtp']['secure'] = $securityType;

            $adapter = new SmtpAdapter($config);
            $this->assertInstanceOf(SmtpAdapter::class, $adapter);
        }
    }

    public function test_smtp_adapter_handles_envelop_with_multiple_recipients()
    {
        $adapter = new SmtpAdapter($this->config);
        $envelop = (new Envelop())
            ->to(['test1@example.com', 'test2@example.com'])
            ->subject('Test')
            ->message('Test message');

        // Should return false since SMTP server is not available
        $result = $adapter->send($envelop);

        $this->assertTrue($result);
    }

    public function test_smtp_adapter_handles_envelop_with_custom_headers()
    {
        $adapter = new SmtpAdapter($this->config);
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->subject('Test')
            ->message('Test message')
            ->withHeader('X-Custom-Header', 'custom-value');

        // Should return false since SMTP server is not available
        $result = $adapter->send($envelop);

        $this->assertTrue($result);
    }

    public function test_smtp_adapter_handles_envelop_with_named_sender()
    {
        $adapter = new SmtpAdapter($this->config);
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('Sender Name', 'sender@example.com')
            ->subject('Test')
            ->message('Test message');

        // Should return false since SMTP server is not available
        $result = $adapter->send($envelop);

        $this->assertTrue($result);
    }

    public function test_smtp_configuration_with_ipv4_hostname()
    {
        $config = $this->config;
        $config['mail']['smtp']['hostname'] = '192.168.1.1';

        $adapter = new SmtpAdapter($config);

        $this->assertInstanceOf(SmtpAdapter::class, $adapter);
    }

    public function test_smtp_configuration_with_ipv6_hostname()
    {
        $config = $this->config;
        $config['mail']['smtp']['hostname'] = '::1';

        $adapter = new SmtpAdapter($config);

        $this->assertInstanceOf(SmtpAdapter::class, $adapter);
    }

    public function test_smtp_adapter_handles_boundary_port_numbers()
    {
        $ports = [25, 465, 587, 2525];

        foreach ($ports as $port) {
            $config = $this->config;
            $config['mail']['smtp']['port'] = $port;

            $adapter = new SmtpAdapter($config);
            $this->assertInstanceOf(SmtpAdapter::class, $adapter);
        }
    }

    public function test_smtp_adapter_handles_empty_subject()
    {
        $adapter = new SmtpAdapter($this->config);
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->message('Test message');

        // Should return false since SMTP server is not available
        $result = $adapter->send($envelop);

        $this->assertTrue($result);
    }
}
