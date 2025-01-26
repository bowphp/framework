<?php

namespace Bow\Tests\Application;

use Bow\Application\Application;
use Bow\Container\Capsule;
use Bow\Http\Request;
use Bow\Http\Response;
use Bow\Router\Exception\RouterException;
use Bow\Testing\KernelTesting;
use Bow\Tests\Config\TestingConfiguration;
use Mockery;

class ApplicationTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        Mockery::mock();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function test_instance_of_application()
    {
        $response = Mockery::mock(Response::class);
        $request = Mockery::mock(Request::class);

        $request->allows()->method()->andReturns("GET");
        $request->allows()->capture()->andReturns(null);
        $request->allows()->get("_method")->andReturns("");

        $app = Application::make($request, $response);
        $app->bind(TestingConfiguration::getConfig());

        $this->assertInstanceOf(Application::class, $app);
        $this->assertInstanceOf(Application::class, app('app'));
        $this->assertInstanceOf(Capsule::class, $app->getContainer());
    }

    public function test_one_time_application_boot()
    {
        $response = Mockery::mock(Response::class);
        $request = Mockery::mock(Request::class);

        $request->allows()->method()->andReturns("GET");
        $request->allows()->capture()->andReturns(null);
        $request->allows()->get("_method")->andReturns("");

        $app = Application::make($request, $response);
        $app->bind(TestingConfiguration::getConfig());

        $this->assertInstanceOf(Application::class, $app);
        $this->assertInstanceOf(Application::class, app('app'));
        $this->assertInstanceOf(Capsule::class, $app->getContainer());
    }

    public function test_send_application_with_404_status()
    {
        $this->expectException(RouterException::class);

        $response = Mockery::mock(Response::class);
        $request = Mockery::mock(Request::class);

        // Response mock method
        $response->allows()->addHeader('X-Powered-By', 'Bow Framework');
        $response->allows()->status(404);

        // Request mock method
        $request->allows()->method()->andReturns("GET");
        $request->allows()->capture()->andReturns(null);
        $request->allows()->path()->andReturns("/");
        $request->allows()->get("_method")->andReturns("");

        $config = Mockery::mock(KernelTesting::class);
        $config->allows([
            "offsetGet" => ["root" => ""],
            "offsetExists" => true,
            "boot" => $config,
            "isCli" => false
        ]);

        $app = new Application($request, $response);
        $app->bind($config);
        $app->send();
    }

    public function test_send_application_with_matched_route()
    {
        $response = Mockery::mock(Response::class);
        $request = Mockery::mock(Request::class);

        // Response mock method
        $response->allows()->addHeader('X-Powered-By', 'Bow Framework');
        $response->allows()->status(200);
        $response->allows()->send('work', 200);

        // Request mock method
        $request->allows()->method()->andReturns("GET");
        $request->allows()->capture()->andReturns(null);
        $request->allows()->path()->andReturns("/");
        $request->allows()->get("_method")->andReturns("");

        $config = Mockery::mock(KernelTesting::class);
        $config->allows([
            "offsetGet" => ["root" => ""],
            "offsetExists" => true,
            "boot" => $config,
            "isCli" => false
        ]);

        $app = new Application($request, $response);
        $app->bind($config);

        $app->get('/', function () {
            return "work";
        });

        $this->assertIsBool($app->send());
    }

    public function test_send_application_with_no_matched_route()
    {
        $response = Mockery::mock(Response::class);
        $request = Mockery::mock(Request::class);

        // Response mock method
        $response->allows()->addHeader('X-Powered-By', 'Bow Framework');
        $response->allows()->status(404);

        // Request mock method
        $request->allows()->method()->andReturns("GET");
        $request->allows()->capture()->andReturns(null);
        $request->allows()->path()->andReturns("/name");
        $request->allows()->get("_method")->andReturns("");

        $config = Mockery::mock(KernelTesting::class);
        $config->allows([
            "offsetGet" => ["root" => ""],
            "offsetExists" => true,
            "boot" => $config,
            "isCli" => false
        ]);

        $app = new Application($request, $response);
        $app->bind($config);

        $app->get('/', function () {
            return "not work";
        });

        $this->expectException(RouterException::class);
        $this->assertFalse($app->send());
    }
}
