<?php

namespace Bow\Tests\Application;

use Bow\Application\Application;
use Bow\Application\Exception\ApplicationException;
use Bow\Container\Capsule;
use Bow\Http\Exception\BadRequestException;
use Bow\Http\Exception\HttpException;
use Bow\Http\Request;
use Bow\Http\Response;
use Bow\Router\Exception\RouterException;
use Bow\Router\Route;
use Bow\Testing\KernelTesting;
use Bow\Tests\Config\TestingConfiguration;
use Mockery;

class ApplicationTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        $config->boot();
    }

    public function setUp(): void
    {
        Mockery::mock();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * Create a basic request mock
     */
    private function createRequestMock(string $method = 'GET', string $path = '/'): Request
    {
        $request = Mockery::mock(Request::class);
        $request->allows()->method()->andReturns($method);
        $request->allows()->capture()->andReturns(null);
        $request->allows()->path()->andReturns($path);
        $request->allows()->get("_method")->andReturns("");
        $request->allows()->domain()->andReturns("localhost");

        return $request;
    }

    /**
     * Create a basic response mock
     */
    private function createResponseMock(int $expectedStatus = 200): Response
    {
        $response = Mockery::mock(Response::class);
        $response->allows()->withHeader('X-Powered-By', 'Bow Framework');
        $response->allows()->status($expectedStatus);
        $response->allows()->send(Mockery::any(), Mockery::any())->andReturn('');

        return $response;
    }

    /**
     * Create a basic config mock
     */
    private function createConfigMock(bool $isCli = false): KernelTesting
    {
        $config = Mockery::mock(KernelTesting::class);
        $config->allows([
            "offsetGet" => ["root" => "", "auto_csrf" => false],
            "offsetExists" => true,
            "boot" => $config,
            "isCli" => $isCli
        ]);

        return $config;
    }

    public function test_instance_of_application()
    {
        $request = $this->createRequestMock();
        $response = $this->createResponseMock();

        $app = Application::make($request, $response);
        $app->bind(TestingConfiguration::getConfig());

        $this->assertInstanceOf(Application::class, $app);
        $this->assertInstanceOf(Application::class, app('app'));
        $this->assertInstanceOf(Capsule::class, $app->getContainer());
    }

    public function test_one_time_application_boot()
    {
        $request = $this->createRequestMock();
        $response = $this->createResponseMock();

        $app = Application::make($request, $response);
        $app->bind(TestingConfiguration::getConfig());

        $this->assertInstanceOf(Application::class, $app);
        $this->assertInstanceOf(Application::class, app('app'));
        $this->assertInstanceOf(Capsule::class, $app->getContainer());
    }

    public function test_application_singleton_pattern()
    {
        $request = $this->createRequestMock();
        $response = $this->createResponseMock();

        $app1 = Application::make($request, $response);
        $app2 = Application::make($request, $response);

        $this->assertSame($app1, $app2);
    }

    public function test_get_router_returns_router_instance()
    {
        $request = $this->createRequestMock();
        $response = $this->createResponseMock();

        $app = new Application($request, $response);
        $router = $app->getRouter();

        $this->assertInstanceOf(\Bow\Router\Router::class, $router);
    }

    public function test_is_running_on_cli()
    {
        $request = $this->createRequestMock();
        $response = $this->createResponseMock();

        $app = new Application($request, $response);
        $isCli = $app->isRunningOnCli();

        $this->assertIsBool($isCli);
        $this->assertEquals(php_sapi_name() == 'cli', $isCli);
    }

    public function test_disable_powered_by_mention()
    {
        $request = $this->createRequestMock();
        $response = Mockery::mock(Response::class);

        // Should NOT call withHeader for X-Powered-By
        $response->shouldNotReceive('withHeader')->with('X-Powered-By', Mockery::any());
        $response->allows()->status(200);
        $response->allows()->send(Mockery::any(), Mockery::any())->andReturn('');

        $config = $this->createConfigMock();

        $app = new Application($request, $response);
        $app->disablePoweredByMention();
        $app->bind($config);

        $app->getRouter()->get('/', function () {
            return 'test';
        });

        $app->run();

        $this->assertTrue(true); // If we get here without Mockery exception, test passes
    }

    public function test_send_application_with_404_status()
    {
        $this->expectException(RouterException::class);
        $this->expectExceptionMessage('Route "/non-existent-path" not found');

        $request = $this->createRequestMock('GET', '/non-existent-path');
        $response = $this->createResponseMock(404);
        $config = $this->createConfigMock();

        $app = new Application($request, $response);
        $app->bind($config);
        $app->run();
    }

    /**
     * @throws BadRequestException
     * @throws \ReflectionException
     * @throws RouterException
     */
    public function test_send_application_with_matched_route()
    {
        $request = $this->createRequestMock();
        $response = $this->createResponseMock();
        $config = $this->createConfigMock();

        $app = new Application($request, $response);
        $app->bind($config);

        $app->getRouter()->get('/', function () {
            return "work";
        });

        $this->assertTrue($app->run());
    }

    public function test_send_application_with_no_matched_route()
    {
        $this->expectException(RouterException::class);

        $request = $this->createRequestMock('GET', '/name');
        $response = $this->createResponseMock(404);
        $config = $this->createConfigMock();

        $app = new Application($request, $response);
        $app->bind($config);

        $app->getRouter()->get('/', function () {
            return "not work";
        });

        $this->assertFalse($app->run());
    }

    public function test_post_request_routing()
    {
        $request = $this->createRequestMock('POST', '/users');
        $response = $this->createResponseMock();
        $config = $this->createConfigMock();

        $app = new Application($request, $response);
        $app->bind($config);

        $app->getRouter()->post('/users', function () {
            return ['created' => true];
        });

        $this->assertTrue($app->run());
    }

    public function test_put_request_routing()
    {
        $request = $this->createRequestMock('PUT', '/users/1');
        $response = $this->createResponseMock();
        $config = $this->createConfigMock();

        $app = new Application($request, $response);
        $app->bind($config);

        $app->getRouter()->put('/users/1', function () {
            return ['updated' => true];
        });

        $this->assertTrue($app->run());
    }

    public function test_delete_request_routing()
    {
        $request = $this->createRequestMock('DELETE', '/users/1');
        $response = $this->createResponseMock();
        $config = $this->createConfigMock();

        $app = new Application($request, $response);
        $app->bind($config);

        $app->getRouter()->delete('/users/1', function () {
            return ['deleted' => true];
        });

        $this->assertTrue($app->run());
    }

    public function test_patch_request_routing()
    {
        $request = $this->createRequestMock('PATCH', '/users/1');
        $response = $this->createResponseMock();
        $config = $this->createConfigMock();

        $app = new Application($request, $response);
        $app->bind($config);

        $app->getRouter()->patch('/users/1', function () {
            return ['patched' => true];
        });

        $this->assertTrue($app->run());
    }

    public function test_any_request_routing()
    {
        $request = $this->createRequestMock('GET', '/api/endpoint');
        $response = $this->createResponseMock();
        $config = $this->createConfigMock();

        $app = new Application($request, $response);
        $app->bind($config);

        $app->getRouter()->any('/api/endpoint', function () {
            return 'any method works';
        });

        $this->assertTrue($app->run());
    }

    public function test_application_with_cli_mode()
    {
        $request = $this->createRequestMock();
        $response = $this->createResponseMock();
        $config = $this->createConfigMock(true);

        $app = new Application($request, $response);
        $app->bind($config);

        // In CLI mode, run() should return true immediately
        $this->assertTrue($app->run());
    }

    public function test_abort_method_throws_http_exception()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Not Found');

        $request = $this->createRequestMock();
        $response = Mockery::mock(Response::class);
        $response->allows()->status(Mockery::any());
        $response->allows()->withHeader(Mockery::any(), Mockery::any());

        $app = new Application($request, $response);

        $app->abort(404, 'Not Found');
    }

    public function test_abort_method_with_headers()
    {
        $this->expectException(HttpException::class);

        $request = $this->createRequestMock();
        $response = Mockery::mock(Response::class);
        $response->allows()->status(Mockery::any());
        $response->shouldReceive('withHeader')->with('X-Custom-Header', 'value')->once();

        $app = new Application($request, $response);

        $app->abort(403, 'Forbidden', ['X-Custom-Header' => 'value']);
    }

    public function test_container_method_returns_capsule()
    {
        $request = $this->createRequestMock();
        $response = $this->createResponseMock();

        $app = new Application($request, $response);

        $this->assertInstanceOf(Capsule::class, $app->container());
    }

    public function test_container_method_resolves_binding()
    {
        $request = $this->createRequestMock();
        $response = $this->createResponseMock();

        $app = new Application($request, $response);
        $app->container('test', fn() => 'test-value');

        $this->assertEquals('test-value', $app->container('test'));
    }

    public function test_container_method_throws_exception_on_invalid_callable()
    {
        $this->expectException(\TypeError::class);

        $request = $this->createRequestMock();
        $response = $this->createResponseMock();

        $app = new Application($request, $response);
        $app->container('test', 'not-callable');
    }

    public function test_rest_method_creates_resource_routes()
    {
        $request = $this->createRequestMock();
        $response = $this->createResponseMock();

        $app = new Application($request, $response);

        $result = $app->rest('/api/users', 'UserController');

        $this->assertInstanceOf(Application::class, $result);
    }

    public function test_rest_method_with_array_configuration()
    {
        $request = $this->createRequestMock();
        $response = $this->createResponseMock();

        $app = new Application($request, $response);

        $result = $app->rest('/api/posts', [
            'controller' => 'PostController',
            'ignores' => ['destroy']
        ]);

        $this->assertInstanceOf(Application::class, $result);
    }

    public function test_rest_method_throws_exception_on_missing_controller()
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('[REST] No defined controller!');

        $request = $this->createRequestMock();
        $response = $this->createResponseMock();

        $app = new Application($request, $response);
        $app->rest('/api/users', ['ignores' => ['destroy']]);
    }

    public function test_magic_call_delegates_to_router()
    {
        $request = $this->createRequestMock();
        $response = $this->createResponseMock();

        $app = new Application($request, $response);

        // Test that we can call router methods via __call
        $route = $app->get('/test', function () {
            return 'test';
        });

        $this->assertInstanceOf(Route::class, $route);
    }

    public function test_magic_call_throws_exception_on_invalid_method()
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('Method [nonExistentMethod] does not exist in Application.');

        $request = $this->createRequestMock();
        $response = $this->createResponseMock();

        $app = new Application($request, $response);
        $app->nonExistentMethod();
    }

    public function test_send_method_executes_run()
    {
        $request = $this->createRequestMock();
        $response = $this->createResponseMock();
        $config = $this->createConfigMock();

        $app = new Application($request, $response);
        $app->bind($config);

        $app->getRouter()->get('/', function () {
            return 'sent';
        });

        // send() method should execute without throwing
        ob_start();
        $app->send();
        $output = ob_get_clean();

        $this->assertTrue(true); // If we reach here, send() worked
    }

    public function test_invoke_with_params_returns_capsule()
    {
        $request = $this->createRequestMock();
        $response = $this->createResponseMock();

        $app = new Application($request, $response);

        // With any number of params, __invoke returns capsule based on count($params) > 0
        $result = $app('test');

        $this->assertInstanceOf(Capsule::class, $result);
    }
}
