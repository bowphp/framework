<?php

namespace Bow\Tests\Mail;

use Bow\Mail\Adapters\LogAdapter;
use Bow\Mail\Envelop;
use Bow\Tests\Config\TestingConfiguration;
use PHPUnit\Framework\TestCase;

class LogAdapterTest extends TestCase
{
    private string $testLogPath;

    protected function setUp(): void
    {
        $this->testLogPath = sys_get_temp_dir() . '/bow_mail_test_' . uniqid();
    }

    protected function tearDown(): void
    {
        // Clean up test log directory
        if (is_dir($this->testLogPath)) {
            $files = glob($this->testLogPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testLogPath);
        }
    }

    public function test_log_adapter_can_be_instantiated()
    {
        $adapter = new LogAdapter();

        $this->assertInstanceOf(LogAdapter::class, $adapter);
    }

    public function test_log_adapter_can_be_instantiated_with_config()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);

        $this->assertInstanceOf(LogAdapter::class, $adapter);
    }

    public function test_log_adapter_creates_directory_if_not_exists()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        new LogAdapter($config);

        $this->assertDirectoryExists($this->testLogPath);
    }

    public function test_log_adapter_uses_default_path_when_not_configured()
    {
        $adapter = new LogAdapter([]);

        $this->assertInstanceOf(LogAdapter::class, $adapter);
    }

    public function test_log_adapter_sends_email_and_creates_file()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->message('Test Message');

        $result = $adapter->send($envelop);

        $this->assertTrue($result);
        
        // Verify file was created
        $files = glob($this->testLogPath . '/*.eml');
        $this->assertCount(1, $files);
    }

    public function test_log_adapter_file_contains_correct_headers()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->message('Test Message');

        $adapter->send($envelop);

        $files = glob($this->testLogPath . '/*.eml');
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString('Date:', $content);
        $this->assertStringContainsString('To: test@example.com', $content);
        $this->assertStringContainsString('Subject: Test Subject', $content);
    }

    public function test_log_adapter_file_contains_message_content()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->message('Test Message Content');

        $adapter->send($envelop);

        $files = glob($this->testLogPath . '/*.eml');
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString('Test Message Content', $content);
    }

    public function test_log_adapter_handles_multiple_recipients()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to(['test1@example.com', 'test2@example.com', 'test3@example.com'])
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->message('Test Message');

        $result = $adapter->send($envelop);

        $this->assertTrue($result);

        $files = glob($this->testLogPath . '/*.eml');
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString('test1@example.com', $content);
        $this->assertStringContainsString('test2@example.com', $content);
        $this->assertStringContainsString('test3@example.com', $content);
    }

    public function test_log_adapter_handles_named_recipients()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to('Recipient Name <test@example.com>')
            ->from('sender@example.com', 'Sender Name')
            ->subject('Test Subject')
            ->message('Test Message');

        $adapter->send($envelop);

        $files = glob($this->testLogPath . '/*.eml');
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString('Recipient Name <test@example.com>', $content);
    }

    public function test_log_adapter_creates_unique_filenames()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com')
            ->subject('Test')
            ->message('Message');

        // Send multiple emails
        $adapter->send($envelop);
        $adapter->send($envelop);
        $adapter->send($envelop);

        $files = glob($this->testLogPath . '/*.eml');
        $this->assertCount(3, $files);
        
        // Verify all filenames are unique
        $this->assertEquals(count($files), count(array_unique($files)));
    }

    public function test_log_adapter_filename_format()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com')
            ->subject('Test')
            ->message('Message');

        $adapter->send($envelop);

        $files = glob($this->testLogPath . '/*.eml');
        $filename = basename($files[0]);

        // Check format: YYYY-MM-DD_HH-MM-SS_XXXXXX.eml
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_[a-zA-Z0-9]{6}\.eml$/', $filename);
    }

    public function test_log_adapter_handles_html_content()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $htmlContent = '<html><body><h1>Test HTML</h1><p>Paragraph</p></body></html>';
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com')
            ->subject('HTML Test')
            ->html($htmlContent);

        $result = $adapter->send($envelop);

        $this->assertTrue($result);

        $files = glob($this->testLogPath . '/*.eml');
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString($htmlContent, $content);
    }

    public function test_log_adapter_handles_custom_headers()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com')
            ->subject('Test')
            ->message('Message')
            ->withHeader('X-Custom-Header', 'CustomValue')
            ->withHeader('X-Priority', '1');

        $adapter->send($envelop);

        $files = glob($this->testLogPath . '/*.eml');
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString('X-Custom-Header: CustomValue', $content);
        $this->assertStringContainsString('X-Priority: 1', $content);
    }

    public function test_log_adapter_handles_cc_recipients()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->addCc('cc@example.com')
            ->from('sender@example.com')
            ->subject('Test')
            ->message('Message');

        $adapter->send($envelop);

        $files = glob($this->testLogPath . '/*.eml');
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString('Cc:', $content);
        $this->assertStringContainsString('cc@example.com', $content);
    }

    public function test_log_adapter_handles_bcc_recipients()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->bcc('bcc@example.com')
            ->from('sender@example.com')
            ->subject('Test')
            ->message('Message');

        $adapter->send($envelop);

        $files = glob($this->testLogPath . '/*.eml');
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString('Bcc:', $content);
        $this->assertStringContainsString('bcc@example.com', $content);
    }

    public function test_log_adapter_handles_reply_to()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com')
            ->replyTo('reply@example.com')
            ->subject('Test')
            ->message('Message');

        $adapter->send($envelop);

        $files = glob($this->testLogPath . '/*.eml');
        $content = file_get_contents($files[0]);

        // Note: There's a typo in Envelop.php - it uses 'Replay-To' instead of 'Reply-To'
        $this->assertStringContainsString('Replay-To:', $content);
        $this->assertStringContainsString('reply@example.com', $content);
    }

    public function test_log_adapter_handles_utf8_content()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com')
            ->subject('UTF-8 Test: 你好世界')
            ->message('Message with UTF-8: こんにちは, مرحبا, Здравствуй');

        $result = $adapter->send($envelop);

        $this->assertTrue($result);

        $files = glob($this->testLogPath . '/*.eml');
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString('你好世界', $content);
        $this->assertStringContainsString('こんにちは', $content);
        $this->assertStringContainsString('مرحبا', $content);
        $this->assertStringContainsString('Здравствуй', $content);
    }

    public function test_log_adapter_handles_long_message()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $longMessage = str_repeat('This is a long message. ', 1000);
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com')
            ->subject('Long Message Test')
            ->message($longMessage);

        $result = $adapter->send($envelop);

        $this->assertTrue($result);

        $files = glob($this->testLogPath . '/*.eml');
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString($longMessage, $content);
    }

    public function test_log_adapter_handles_special_characters_in_subject()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com')
            ->subject('Special Chars: éàü & <> "quotes"')
            ->message('Test Message');

        $result = $adapter->send($envelop);

        $this->assertTrue($result);

        $files = glob($this->testLogPath . '/*.eml');
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString('Special Chars:', $content);
    }

    public function test_log_adapter_returns_true_on_successful_send()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com')
            ->subject('Test')
            ->message('Message');

        $result = $adapter->send($envelop);

        $this->assertTrue($result);
        $this->assertIsBool($result);
    }

    public function test_log_adapter_handles_multiple_mixed_recipients()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to(['John Doe <john@example.com>', 'jane@example.com', 'Bob Smith <bob@example.com>'])
            ->from('sender@example.com')
            ->subject('Test')
            ->message('Message');

        $result = $adapter->send($envelop);

        $this->assertTrue($result);

        $files = glob($this->testLogPath . '/*.eml');
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString('John Doe <john@example.com>', $content);
        $this->assertStringContainsString('jane@example.com', $content);
        $this->assertStringContainsString('Bob Smith <bob@example.com>', $content);
    }

    public function test_log_adapter_file_is_readable()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com')
            ->subject('Test')
            ->message('Message');

        $adapter->send($envelop);

        $files = glob($this->testLogPath . '/*.eml');
        
        $this->assertFileExists($files[0]);
        $this->assertFileIsReadable($files[0]);
    }

    public function test_log_adapter_preserves_message_structure()
    {
        $config = [
            'path' => $this->testLogPath
        ];

        $adapter = new LogAdapter($config);
        
        $message = "Line 1\nLine 2\nLine 3\n\nParagraph 2";
        
        $envelop = (new Envelop())
            ->to('test@example.com')
            ->from('sender@example.com')
            ->subject('Test')
            ->message($message);

        $adapter->send($envelop);

        $files = glob($this->testLogPath . '/*.eml');
        $content = file_get_contents($files[0]);

        $this->assertStringContainsString("Line 1\nLine 2\nLine 3", $content);
        $this->assertStringContainsString("Paragraph 2", $content);
    }
}
