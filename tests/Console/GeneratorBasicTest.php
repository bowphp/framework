<?php

namespace Bow\Tests\Console;

use Bow\Console\Generator;

class GeneratorBasicTest extends \PHPUnit\Framework\TestCase
{
    public function test_generate_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'CreateUserCommand');
        $content = $generator->makeStubContent('command', [
            "namespace" => "",
            "className" => "CreateUserCommand",
            "baseNamespace" => "Generator\Testing",
        ]);

        $this->assertNotNull($content);
        $this->assertRegExp("@\nnamespace\sGenerator\\\Testing;\n@", $content);
        $this->assertRegExp("@\nclass\sCreateUserCommand\sextends\sConsoleCommand\n@", $content);
    }

    public function test_generate_stub_without_data()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'CreateUserCommand');
        $content = $generator->makeStubContent('command', []);

        $this->assertNotNull($content);
        $this->assertRegExp("@\nnamespace\s\{baseNamespace\}\{namespace\};\n@", $content);
        $this->assertRegExp("@\nclass\s\{className\}\sextends\sConsoleCommand\n@", $content);
    }

    public function test_generate_by_writing_file()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'CreateUserCommand');
        $result = $generator->write('command', [
            "baseNamespace" => "Generator\Testing",
        ]);

        $this->assertTrue($result);
        $this->assertTrue(file_exists($generator->getPath()));
    }
}
