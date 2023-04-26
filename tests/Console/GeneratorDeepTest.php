<?php

namespace Bow\Tests\Console;

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
        $this->assertRegExp("@\nclass\sFakeEvent\simplements\sAppEvent\n@", $content);
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

    public function test_generate_cache_migration_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'FakeCacheMigration');
        $content = $generator->makeStubContent('model/cache', [
            "className" => "FakeCacheMigration",
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nclass\sFakeCacheMigration\sextends\sMigration\n@", $content);
    }

    public function test_generate_session_migration_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'FakeSessionMigration');
        $content = $generator->makeStubContent('model/session', [
            "className" => "FakeSessionMigration",
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nclass\sFakeSessionMigration\sextends\sMigration\n@", $content);
    }

    public function test_generate_table_migration_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'FakeTableMigration');
        $content = $generator->makeStubContent('model/table', [
            "className" => "FakeTableMigration",
            "table" => "fakers",
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nclass\sFakeTableMigration\sextends\sMigration\n@", $content);
    }

    public function test_generate_create_migration_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'FakeCreateTableMigration');
        $content = $generator->makeStubContent('model/create', [
            "className" => "FakeCreateTableMigration",
            "table" => "fakers",
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nclass\sFakeCreateTableMigration\sextends\sMigration\n@", $content);
    }

    public function test_generate_standard_migration_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'FakeStandardTableMigration');
        $content = $generator->makeStubContent('model/standard', [
            "className" => "FakeStandardTableMigration",
            "table" => "fakers",
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nclass\sFakeStandardTableMigration\sextends\sMigration\n@", $content);
    }

    public function test_generate_model_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'Example');
        $content = $generator->makeStubContent('model/model', [
            "className" => "Example",
            "table" => "examples",
            "baseNamespace" => "App\\",
            "namespace" => "Models"
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nclass\sExample\sextends\sModel\n@", $content);
    }

    public function test_generate_controller_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'ExampleController');
        $content = $generator->makeStubContent('controller/controller', [
            "className" => "ExampleController",
            "baseNamespace" => "App\\",
            "namespace" => "Controllers"
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp("@\nclass\sExampleController\sextends\sController\n@", $content);
    }

    public function test_generate_controller_no_plain_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'ExampleController');
        $content = $generator->makeStubContent('controller/no-plain', [
            "className" => "ExampleController",
            "baseNamespace" => "App\\",
            "namespace" => "Controllers"
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp('@\nclass\sExampleController\sextends\sController\n@', $content);
        $this->assertRegExp('@public\sfunction\sindex()@', $content);
        $this->assertRegExp('@public\sfunction\screate()@', $content);
        $this->assertRegExp('@public\sfunction\supdate\(Request\s\$request,\smixed\s\$id\)@', $content);
        $this->assertRegExp('@public\sfunction\sshow\(mixed\s\$id\)@', $content);
        $this->assertRegExp('@public\sfunction\sedit\(mixed\s\$id\)@', $content);
        $this->assertRegExp('@public\sfunction\sstore\(Request\s\$request\)@', $content);
        $this->assertRegExp('@public\sfunction\sdestroy\(mixed\s\$id\)@', $content);
    }

    public function test_generate_controller_rest_stubs()
    {
        $generator = new Generator(TESTING_RESOURCE_BASE_DIRECTORY, 'ExampleController');
        $content = $generator->makeStubContent('controller/rest', [
            "className" => "ExampleController",
            "baseNamespace" => "App\\",
            "namespace" => "Controllers"
        ]);

        $this->assertNotNull($content);
        $this->assertMatchesSnapshot($content);
        $this->assertRegExp('@\nclass\sExampleController\sextends\sController\n@', $content);
        $this->assertRegExp('@public\sfunction\sindex()@', $content);
        $this->assertRegExp('@public\sfunction\supdate\(Request\s\$request,\smixed\s\$id\)@', $content);
        $this->assertRegExp('@public\sfunction\sshow\(Request\s\$request,\smixed\s\$id\)@', $content);
        $this->assertRegExp('@public\sfunction\sstore\(Request\s\$request\)@', $content);
        $this->assertRegExp('@public\sfunction\sdestroy\(Request\s\$request,\smixed\s\$id\)@', $content);
    }
}
