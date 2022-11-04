<?php

namespace Bow\Tests\Application;

use Mockery;
use Bow\Http\Request;
use Bow\Http\Response;
use Bow\Application\Application;
use Bow\Container\Capsule;
use Bow\Tests\Config\TestingConfiguration;

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
        $request->allows()->get("_method")->andReturns("");

        $app = Application::make($request, $response);
        $app->bind(TestingConfiguration::getConfig());

        $this->assertInstanceOf(Application::class, $app);
        $this->assertInstanceOf(Application::class, app('app'));
        $this->assertInstanceOf(Capsule::class, $app->getContainer());
    }
}
