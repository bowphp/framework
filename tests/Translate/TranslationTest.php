<?php

namespace Bow\Tests\Translate;

use Bow\Tests\Config\TestingConfiguration;
use Bow\Translate\Translator;

class TranslationTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Translator::configure($config['translate.lang'], $config["translate.dictionary"]);
    }

    public function test_fr_welcome_message()
    {
        $this->assertEquals('Bow framework', Translator::translate('welcome.message'));
    }

    public function test_fr_user_name()
    {
        $this->assertEquals('Franck', Translator::translate('welcome.user.name'));
    }

    public function test_fr_plurial()
    {
        $this->assertEquals('Utilisateurs', Translator::plural('welcome.plurial'));
    }

    public function test_fr_single()
    {
        $this->assertEquals('Utilisateur', Translator::single('welcome.plurial'));
    }

    public function test_fr_bind_data()
    {
        $this->assertEquals('Bonjour papac', Translator::single('welcome.hello', ['name' => 'papac']));
    }

    public function test_en_welcome_message()
    {
        Translator::setLocale("en");
        $this->assertEquals('Bow framework', Translator::translate('welcome.message'));
    }

    public function test_en_user_name()
    {
        Translator::setLocale("en");
        $this->assertEquals('Franck', Translator::translate('welcome.user.name'));
    }

    public function test_en_plurial()
    {
        Translator::setLocale("en");
        $this->assertEquals('Users', Translator::plural('welcome.plurial'));
    }

    public function test_en_single()
    {
        Translator::setLocale("en");
        $this->assertEquals('User', Translator::single('welcome.plurial'));
    }

    public function test_en_bind_data()
    {
        Translator::setLocale("en");
        $this->assertEquals('Hello papac', Translator::single('welcome.hello', ['name' => 'papac']));
    }
}
