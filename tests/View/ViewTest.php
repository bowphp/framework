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
        foreach (glob(TESTING_RESOURCE_BASE_DIRECTORY . '/cache/*.php') as $value) {
            @unlink($value);
        }

        foreach (glob(TESTING_RESOURCE_BASE_DIRECTORY . '/cache/**/*.php') as $value) {
            @unlink($value);
            @rmdir(dirname($value));
        }
    }

    public function test_twig_compilation()
    {
        View::getInstance()->cachable(false);

        $result = View::parse('twig', ['name' => 'bow', 'engine' => 'twig']);

        $this->assertEquals(trim($result), '<p>bow see hello world by twig</p>');
    }

    public function test_tintin_compilation()
    {
        View::getInstance()->setEngine('tintin')->setExtension('.tintin.php')->cachable(false);

        $result = View::parse('tintin', ['name' => 'bow', 'engine' => 'tintin']);

        $this->assertEquals(trim($result), '<p>bow see hello world by tintin</p>');
    }

    public function test_php_compilation()
    {
        View::getInstance()->setEngine('php')->setExtension('.php')->cachable(false);

        $result = View::parse('php', ['name' => 'bow', 'engine' => 'php']);

        $this->assertEquals(trim($result), '<p>bow see hello world by php</p>');
    }

    public function test_file_exists()
    {
        View::getInstance()->fileExists('php');

        $this->assertTrue(View::getInstance()->fileExists('php'));
    }
}
