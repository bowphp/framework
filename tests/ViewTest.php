<?php

use \Bow\View\View;

class ViewTest extends \PHPUnit\Framework\TestCase
{
    public function config()
    {
        return \Bow\Application\Configuration::configure(__DIR__);
    }

    public function testTwigCompilation()
    {
        View::configure($this->config());
        View::singleton()->cachable(false);

        $resultat = View::make('twig', ['name' => 'bow', 'engine' => 'twig']);
        $this->assertEquals(trim($resultat), '<p>bow see hello world by twig</p>');
    }

    public function testMustacheCompilation()
    {
        View::singleton()->setEngine('mustache')->setExtension('.tpl')->cachable(false);

        $resultat = View::make('mustache', ['name' => 'bow', 'engine' => 'mustache']);
        $this->assertEquals(trim($resultat), '<p>bow see hello world by mustache</p>');
    }

    public function testPugCompilation()
    {
        View::singleton()->setEngine('pug')->setExtension('.pug')->cachable(false);

        $resultat = View::make('pug', ['name' => 'bow', 'engine' => 'pug']);
        $this->assertEquals(trim($resultat), '<p>bow see hello world by pug</p>');
    }

    public function testPHPCompilation()
    {
        View::singleton()->setEngine('php')->setExtension('.php')->cachable(false);
        $resultat = View::make('php', ['name' => 'bow', 'engine' => 'php']);
        $this->assertEquals(trim($resultat), '<p>bow see hello world by php</p>');
    }

    public function __destruct()
    {
        foreach (glob(__DIR__.'/data/cache/view/*.php') as $value) {
            @unlink($value);
        }

        foreach (glob(__DIR__.'/data/cache/*.php') as $value) {
            @unlink($value);
        }

        foreach (glob(__DIR__.'/data/cache/view/*/*.php') as $value) {
            @unlink($value);
            @rmdir(dirname($value));
        }
    }
}