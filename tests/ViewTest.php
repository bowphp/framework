<?php

use \Bow\View\View;

class ViewTest extends \PHPUnit\Framework\TestCase
{
    public function config()
    {
        return \Bow\Application\Configuration::configure([
            'application' => require realpath(__DIR__.'/config/application.php')
        ]);
    }

    public function testTwigCompilation()
    {
        View::configure($this->config());
        View::singleton()->cachable(false);
        $resultat = View::make('twig', ['name' => 'bow']);
        $this->assertEquals(trim($resultat), '<p>bow see hello world</p>');
    }

    public function testMustacheCompilation()
    {
        View::singleton()->cachable(false)->setEngine('mustache')->setExtension('.tpl');

        $resultat = View::make('mustache', ['name' => 'bow']);
        $this->assertEquals(trim($resultat), '<p>bow see hello world</p>');
    }

    public function testPugCompilation()
    {
        View::singleton()->cachable(false)->setEngine('pug')->setExtension('.pug');
        $resultat = View::make('pug', ['name' => 'bow']);
        $this->assertEquals(trim($resultat), '<p>bow see hello world</p>');
        @unlink(__DIR__.'/data/cache/view/CeCm7UI4pbH_339llpkuqpiQxwVYQ7Bqr7lMdJJXWY05Z0ED7b0K5WbtH_9lgYvqRDhrm2DDAKcKD3hcOXxYYg.php');
    }

    public function testPHPCompilation()
    {
        View::singleton()->cachable(false)->setEngine('php')->setExtension('.php');
        $resultat = View::make('php', ['name' => 'bow']);
        $this->assertEquals(trim($resultat), '<p>bow see hello world</p>');
    }
}