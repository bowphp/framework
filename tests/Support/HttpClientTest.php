<?php

namespace Bow\Tests\Support;

use Bow\Http\Client\HttpClient;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{
    public function test_get_method()
    {
        $http = new HttpClient();

        $response = $http->get("https://google.com");

        $this->assertEquals($response->statusCode(), 200);
    }

    public function test_get_method_with_custom_headers()
    {
        $http = new HttpClient();

        $http->addHeaders(["X-Api-Key" => "Fake-Key"]);
        $response = $http->get("https://google.com");

        $this->assertEquals($response->statusCode(), 200);
    }

    public function test_should_be_fail_with_get_method()
    {
        $http = new HttpClient("https://google.com");

        $http->addHeaders(["X-Api-Key" => "Fake-Key"]);
        $response = $http->get("/the-fake-url");

        $this->assertEquals($response->statusCode(), 404);
    }
}
