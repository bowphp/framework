<?php

use Bow\Support\Str;
use Bow\Console\Generator;
use Spatie\Snapshots\MatchesSnapshots;

class GeneratorDeepTest extends \PHPUnit\Framework\TestCase
{
    use MatchesSnapshots;

    public function test_generate_command_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'FakeCommand');
        $content = $generator->makeStubContent('command', [
            "namespace" => "",
            "className" => "FakeCommand",
            "baseNamespace" => "App\Commands",
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nnamespace\sApp\\\Commands;\n@", $content);
        $this->assertRegExp("@\nclass\sFakeCommand\sextends\sConsoleCommand\n@", $content);
    }

    public function test_generate_configuration_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'FakeConfiguration');
        $content = $generator->makeStubContent('configuration', [
            "namespace" => "",
            "className" => "FakeConfiguration",
            "baseNamespace" => "App\Configurations",
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nnamespace\sApp\\\Configurations;\n@", $content);
        $this->assertRegExp("@\nclass\sFakeConfiguration\sextends\sConfiguration\n@", $content);
    }

    public function test_generate_event_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'FakeEvent');
        $content = $generator->makeStubContent('event', [
            "namespace" => "",
            "className" => "FakeEvent",
            "baseNamespace" => "App\Events",
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nnamespace\sApp\\\Events;\n@", $content);
        $this->assertRegExp("@\nclass\sFakeEvent\simplements\sApplicationEvent\n@", $content);
    }

    public function test_generate_exception_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'FakeException');
        $content = $generator->makeStubContent('exception', [
            "namespace" => "",
            "className" => "FakeException",
            "baseNamespace" => "App\Exceptions",
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nnamespace\sApp\\\Exceptions;\n@", $content);
        $this->assertRegExp("@\nclass\sFakeException\sextends\sException\n@", $content);
    }

    public function test_generate_listener_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'FakeListener');
        $content = $generator->makeStubContent('listener', [
            "namespace" => "",
            "className" => "FakeListener",
            "baseNamespace" => "App\Listeners",
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nnamespace\sApp\\\Listeners;\n@", $content);
        $this->assertRegExp("@\nclass\sFakeListener\simplements\sEventListener\n@", $content);
    }

    public function test_generate_middleware_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'FakeMiddleware');
        $content = $generator->makeStubContent('middleware', [
            "namespace" => "",
            "className" => "FakeMiddleware",
            "baseNamespace" => "App\Middlewares",
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nnamespace\sApp\\\Middlewares;\n@", $content);
        $this->assertRegExp("@\nclass\sFakeMiddleware\simplements\sBaseMiddleware\n@", $content);
    }

    public function test_generate_producer_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'FakeProducer');
        $content = $generator->makeStubContent('producer', [
            "namespace" => "",
            "className" => "FakeProducer",
            "baseNamespace" => "App\Producers",
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nnamespace\sApp\\\Producers;\n@", $content);
        $this->assertRegExp("@\nclass\sFakeProducer\sextends\sProducerService\n@", $content);
    }

    public function test_generate_seeder_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'fake_seeder');
        $content = $generator->makeStubContent('seeder', [
            'num' => 1,
            'name' => "fakes"
        ]);
        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertMatchesSnapshot($content);
    }

    public function test_generate_service_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'FakeService');
        $content = $generator->makeStubContent('service', [
            "namespace" => "",
            "className" => "FakeService",
            "baseNamespace" => "App\Services",
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nnamespace\sApp\\\Services;\n@", $content);
        $this->assertRegExp("@\nclass\sFakeService\n@", $content);
    }

    public function test_generate_validation_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'FakeValidationRequest');
        $content = $generator->makeStubContent('validation', [
            "namespace" => "",
            "className" => "FakeValidationRequest",
            "baseNamespace" => "App\Validations",
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nnamespace\sApp\\\Validations;\n@", $content);
        $this->assertRegExp("@\nclass\sFakeValidationRequest\sextends\sRequestValidation\n@", $content);
    }
}
