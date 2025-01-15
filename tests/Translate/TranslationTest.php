<?php

namespace Bow\Tests\Translate;

use Bow\Translate\Translator;
use Bow\Tests\Config\TestingConfiguration;

class TranslationTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Translator::configure($config['translate.lang'], $config["translate.dictionary"]);
    }

    public function test_fr_welcome_message()
    {
        $this->assertEquals(Translator::translate('welcome.message'), 'bow framework');
    }

    public function test_fr_user_name()
    {
        $this->assertEquals(Translator::translate('welcome.user.name'), 'Franck');
    }

    public function test_fr_plurial()
    {
        $this->assertEquals(Translator::plural('welcome.plurial'), 'Utilisateurs');
    }

    public function test_fr_single()
    {
        $this->assertEquals(Translator::single('welcome.plurial'), 'Utilisateur');
    }

    public function test_fr_bind_data()
    {
        $this->assertEquals(Translator::single('welcome.hello', ['name' => 'papac']), 'Bonjour papac');
    }

    public function test_en_welcome_message()
    {
        Translator::setLocale("en");
        $this->assertEquals(Translator::translate('welcome.message'), 'Bow framework');
    }

    public function test_en_user_name()
    {
        Translator::setLocale("en");
        $this->assertEquals(Translator::translate('welcome.user.name'), 'Frank');
    }

    public function test_en_plurial()
    {
        Translator::setLocale("en");
        $this->assertEquals(Translator::plural('welcome.plurial'), 'Users');
    }

    public function test_en_single()
    {
        Translator::setLocale("en");
        $this->assertEquals(Translator::single('welcome.plurial'), 'User');
    }

    public function test_en_bind_data()
    {
        Translator::setLocale("en");
        $this->assertEquals(Translator::single('welcome.hello', ['name' => 'papac']), 'Hello papac');
    }
}
