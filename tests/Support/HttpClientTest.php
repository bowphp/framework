<?php

namespace Bow\Tests\Support;

use Bow\Http\Client\HttpClient;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{
    // ==================== GET Method Tests ====================

    public function test_get_method_fails_with_invalid_domain()
    {
        $http = new HttpClient();
        $response = $http->get("https://www.oogle.com");

        $this->assertEquals(503, $response->statusCode());
    }

    public function test_get_method_succeeds_with_valid_url()
    {
        $http = new HttpClient();
        $response = $http->get("https://www.google.com");

        $this->assertEquals(200, $response->statusCode());
    }

    public function test_get_method_with_custom_headers()
    {
        $http = new HttpClient();
        $http->withHeaders(["X-Api-Key" => "Fake-Key"]);

        $response = $http->get("https://www.google.com");

        $this->assertEquals(200, $response->statusCode());
    }

    public function test_get_method_fails_with_non_existent_path()
    {
        $http = new HttpClient("https://www.google.com");
        $http->withHeaders(["X-Api-Key" => "Fake-Key"]);

        $response = $http->get("/the-fake-url");

        $this->assertEquals(404, $response->statusCode());
    }

    public function test_get_method_with_base_url_in_constructor()
    {
        $http = new HttpClient("https://www.google.com");
        $response = $http->get("/");

        $this->assertEquals(200, $response->statusCode());
    }

    // ==================== POST Method Tests ====================

    public function test_post_method_with_data()
    {
        $http = new HttpClient();
        $response = $http->post("https://httpbin.org/post", [
            'name' => 'test',
            'value' => 'example'
        ]);

        $this->assertEquals(200, $response->statusCode());
        $this->assertStringContainsString('test', $response->getContent());
    }

    public function test_post_method_with_json_data()
    {
        $http = new HttpClient();
        $http->withHeaders(['Content-Type' => 'application/json']);

        $response = $http->post("https://httpbin.org/post", [
            'name' => 'test',
            'value' => 'example'
        ]);

        $this->assertEquals(200, $response->statusCode());
    }

    // ==================== PUT Method Tests ====================

    public function test_put_method_with_data()
    {
        $http = new HttpClient();
        $response = $http->put("https://httpbin.org/put", [
            'name' => 'updated',
            'value' => 'example'
        ]);

        $this->assertEquals(200, $response->statusCode());
        $this->assertStringContainsString('updated', $response->getContent());
    }

    // ==================== DELETE Method Tests ====================

    public function test_delete_method()
    {
        $http = new HttpClient();
        $response = $http->delete("https://httpbin.org/delete");

        $this->assertEquals(200, $response->statusCode());
    }

    // ==================== Header Tests ====================

    public function test_add_multiple_headers()
    {
        $http = new HttpClient();
        $http->withHeaders([
            "X-Api-Key" => "test-key",
            "X-Custom-Header" => "custom-value"
        ]);

        $response = $http->get("https://httpbin.org/headers");

        $this->assertEquals(200, $response->statusCode());
        $this->assertStringContainsString('test-key', $response->getContent());
    }

    public function test_user_agent_header()
    {
        $http = new HttpClient();
        $http->withHeaders(["User-Agent" => "BowFramework/1.0"]);

        $response = $http->get("https://httpbin.org/user-agent");

        $this->assertEquals(200, $response->statusCode());
        $this->assertStringContainsString('BowFramework', $response->getContent());
    }

    // ==================== Response Tests ====================

    public function test_response_body_is_retrievable()
    {
        $http = new HttpClient();
        $response = $http->get("https://www.google.com");

        $body = $response->getContent();

        $this->assertNotEmpty($body);
        $this->assertIsString($body);
    }

    public function test_response_status_code_is_correct()
    {
        $http = new HttpClient();
        $response = $http->get("https://httpbin.org/status/201");

        $this->assertEquals(201, $response->statusCode());
    }

    // ==================== Error Handling Tests ====================

    public function test_timeout_handling()
    {
        $http = new HttpClient();
        // This should work or timeout gracefully
        $response = $http->get("https://httpbin.org/delay/1");

        $this->assertIsInt($response->statusCode());
    }

    public function test_redirect_following()
    {
        $http = new HttpClient();
        $response = $http->get("https://httpbin.org/redirect/1");

        $this->assertEquals(200, $response->statusCode());
    }
}
