<?php

namespace Bow\Tests\View;

use Bow\Tests\Config\TestingConfiguration;
use Bow\View\View;

class ViewTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();

        View::configure($config["view"]);
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanupCache();
    }

    public function setUp(): void
    {
        // Reset to default twig engine before each test
        View::getInstance()->setEngine('twig')->setExtension('.twig');
    }

    /**
     * Helper method to cleanup cache files
     */
    private static function cleanupCache(): void
    {
        foreach (glob(TESTING_RESOURCE_BASE_DIRECTORY . '/cache/*.php') as $value) {
            @unlink($value);
        }

        foreach (glob(TESTING_RESOURCE_BASE_DIRECTORY . '/cache/**/*.php') as $value) {
            @unlink($value);
            @rmdir(dirname($value));
        }
    }

    /**
     * Helper method to switch engine and extension
     */
    private function switchEngine(string $engine, string $extension): void
    {
        View::getInstance()->setEngine($engine)->setExtension($extension);
    }

    /**
     * Helper method to get trimmed parsed result
     */
    private function parseAndTrim(string $template, array $data = []): string
    {
        return trim((string) View::parse($template, $data));
    }

    public function test_view_instance_is_singleton()
    {
        $instance1 = View::getInstance();
        $instance2 = View::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function test_view_configuration_is_loaded()
    {
        $config = TestingConfiguration::getConfig();
        View::configure($config["view"]);

        $this->assertInstanceOf(\Bow\View\View::class, View::getInstance());
    }

    // Twig Engine Tests

    public function test_twig_compilation()
    {
        $this->switchEngine('twig', '.twig');

        $result = $this->parseAndTrim('twig', ['name' => 'bow', 'engine' => 'twig']);

        $this->assertEquals('<p>bow see hello world by twig</p>', $result);
    }

    public function test_twig_compilation_with_no_engine_parameter()
    {
        $this->switchEngine('twig', '.twig');

        $result = $this->parseAndTrim('twig', ['name' => 'test', 'engine' => 'twig']);

        $this->assertStringContainsString('test', $result);
        $this->assertStringContainsString('twig', $result);
    }

    public function test_twig_compilation_with_complex_data()
    {
        $this->switchEngine('twig', '.twig');

        $data = [
            'name' => 'bow',
            'engine' => 'twig',
            'nested' => ['key' => 'value'],
            'array' => [1, 2, 3]
        ];

        $result = (string) View::parse('twig', $data);

        $this->assertIsString($result);
        $this->assertStringContainsString('bow', $result);
    }

    // Tintin Engine Tests

    public function test_tintin_compilation()
    {
        $this->switchEngine('tintin', '.tintin.php');

        $result = $this->parseAndTrim('tintin', ['name' => 'bow', 'engine' => 'tintin']);

        $this->assertEquals('<p>bow see hello world by tintin</p>', $result);
    }

    public function test_tintin_compilation_with_different_data()
    {
        $this->switchEngine('tintin', '.tintin.php');

        $result = $this->parseAndTrim('tintin', ['name' => 'framework', 'engine' => 'tintin']);

        $this->assertStringContainsString('framework', $result);
        $this->assertStringContainsString('tintin', $result);
    }

    public function test_tintin_compilation_with_complex_data()
    {
        $this->switchEngine('tintin', '.tintin.php');

        $data = [
            'name' => 'bow',
            'engine' => 'tintin',
            'items' => ['item1', 'item2', 'item3']
        ];

        $result = (string) View::parse('tintin', $data);

        $this->assertIsString($result);
        $this->assertStringContainsString('bow', $result);
    }

    // PHP Engine Tests

    public function test_php_compilation()
    {
        $this->switchEngine('php', '.php');

        $result = $this->parseAndTrim('php', ['name' => 'bow', 'engine' => 'php']);

        $this->assertEquals('<p>bow see hello world by php</p>', $result);
    }

    public function test_php_compilation_with_empty_data()
    {
        $this->switchEngine('php', '.php');

        $result = (string) View::parse('php', []);

        $this->assertIsString($result);
        // PHP template has defaults, should still render
        $this->assertStringContainsString('hello world', $result);
    }

    public function test_php_compilation_with_complex_data()
    {
        $this->switchEngine('php', '.php');

        $data = [
            'name' => 'bow',
            'engine' => 'php',
            'config' => ['debug' => true]
        ];

        $result = (string) View::parse('php', $data);

        $this->assertIsString($result);
        $this->assertStringContainsString('bow', $result);
    }

    // Engine Switching Tests

    public function test_can_switch_from_twig_to_tintin()
    {
        $this->switchEngine('twig', '.twig');
        $twigResult = $this->parseAndTrim('twig', ['name' => 'bow', 'engine' => 'twig']);

        $this->switchEngine('tintin', '.tintin.php');
        $tintinResult = $this->parseAndTrim('tintin', ['name' => 'bow', 'engine' => 'tintin']);

        $this->assertEquals('<p>bow see hello world by twig</p>', $twigResult);
        $this->assertEquals('<p>bow see hello world by tintin</p>', $tintinResult);
    }

    public function test_can_switch_from_tintin_to_php()
    {
        $this->switchEngine('tintin', '.tintin.php');
        $tintinResult = $this->parseAndTrim('tintin', ['name' => 'bow', 'engine' => 'tintin']);

        $this->switchEngine('php', '.php');
        $phpResult = $this->parseAndTrim('php', ['name' => 'bow', 'engine' => 'php']);

        $this->assertEquals('<p>bow see hello world by tintin</p>', $tintinResult);
        $this->assertEquals('<p>bow see hello world by php</p>', $phpResult);
    }

    public function test_can_switch_from_php_to_twig()
    {
        $this->switchEngine('php', '.php');
        $phpResult = $this->parseAndTrim('php', ['name' => 'bow', 'engine' => 'php']);

        $this->switchEngine('twig', '.twig');
        $twigResult = $this->parseAndTrim('twig', ['name' => 'bow', 'engine' => 'twig']);

        $this->assertEquals('<p>bow see hello world by php</p>', $phpResult);
        $this->assertEquals('<p>bow see hello world by twig</p>', $twigResult);
    }

    // File Existence Tests

    public function test_file_exists_returns_true_for_existing_file()
    {
        $this->switchEngine('php', '.php');

        $this->assertTrue(View::getInstance()->fileExists('php'));
    }

    public function test_file_exists_returns_false_for_non_existing_file()
    {
        $this->assertFalse(View::getInstance()->fileExists('non_existent_template'));
    }

    public function test_file_exists_for_twig_template()
    {
        $this->switchEngine('twig', '.twig');

        $this->assertTrue(View::getInstance()->fileExists('twig'));
    }

    public function test_file_exists_for_tintin_template()
    {
        $this->switchEngine('tintin', '.tintin.php');

        $this->assertTrue(View::getInstance()->fileExists('tintin'));
    }

    // Engine and Extension Tests

    public function test_set_engine_returns_view_instance()
    {
        $result = View::getInstance()->setEngine('php');

        $this->assertInstanceOf(\Bow\View\View::class, $result);
    }

    public function test_set_extension_returns_view_instance()
    {
        $result = View::getInstance()->setExtension('.php');

        $this->assertInstanceOf(\Bow\View\View::class, $result);
    }

    public function test_engine_and_extension_can_be_chained()
    {
        $result = View::getInstance()
            ->setEngine('php')
            ->setExtension('.php');

        $this->assertInstanceOf(\Bow\View\View::class, $result);
    }

    // Parse Method Tests

    public function test_parse_returns_string()
    {
        $this->switchEngine('php', '.php');

        $result = (string) View::parse('php', ['name' => 'test']);

        $this->assertIsString($result);
    }

    public function test_parse_with_no_data_parameter()
    {
        $this->switchEngine('php', '.php');

        $result = (string) View::parse('php');

        $this->assertIsString($result);
    }

    public function test_parse_interpolates_data_correctly()
    {
        $this->switchEngine('php', '.php');

        $result = (string) View::parse('php', ['name' => 'bow', 'engine' => 'php']);

        $this->assertStringContainsString('bow', $result);
        $this->assertStringContainsString('php', $result);
    }
}
