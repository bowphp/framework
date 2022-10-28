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

    public function testTwigCompilation()
    {
        View::getInstance()->cachable(false);

        $resultat = View::parse('twig', ['name' => 'bow', 'engine' => 'twig']);

        $this->assertEquals(trim($resultat), '<p>bow see hello world by twig</p>');
    }

    public function testTintinCompilation() // 1547890+262589
    {
        View::getInstance()->setEngine('tintin')->setExtension('.tintin.php')->cachable(false);

        $resultat = View::parse('tintin', ['name' => 'bow', 'engine' => 'tintin']);

        $this->assertEquals(trim($resultat), '<p>bow see hello world by tintin</p>');
    }

    public function testPHPCompilation()
    {
        View::getInstance()->setEngine('php')->setExtension('.php')->cachable(false);

        $resultat = View::parse('php', ['name' => 'bow', 'engine' => 'php']);

        $this->assertEquals(trim($resultat), '<p>bow see hello world by php</p>');
    }

    public function __destruct()
    {
        foreach (glob(__DIR__.'/data/cache/view/*.php') as $value) {
            @unlink($value);
        }

        foreach (glob(__DIR__.'/data/cache/*.php') as $value) {
            // @unlink($value);
        }

        foreach (glob(__DIR__.'/data/cache/view/*/*.php') as $value) {
            @unlink($value);
            @rmdir(dirname($value));
        }

        foreach (glob(__DIR__.'/data/cache/*/*.php') as $value) {
            @unlink($value);
            @rmdir(dirname($value));
        }
    }
}
