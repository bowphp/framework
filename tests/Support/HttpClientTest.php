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

    // ==================== Authentication Tests ====================

    public function test_basic_auth_with_valid_credentials()
    {
        $http = new HttpClient();
        $http->basicAuth('user', 'passwd');

        $response = $http->get("https://httpbin.org/basic-auth/user/passwd");

        $this->assertEquals(200, $response->statusCode());
        $this->assertStringContainsString('authenticated', $response->getContent());
    }

    public function test_basic_auth_with_invalid_credentials()
    {
        $http = new HttpClient();
        $http->basicAuth('wrong', 'credentials');

        $response = $http->get("https://httpbin.org/basic-auth/user/passwd");

        $this->assertEquals(401, $response->statusCode());
    }

    public function test_bearer_auth_sends_token_in_header()
    {
        $http = new HttpClient();
        $http->bearerAuth('my-test-token');

        $response = $http->get("https://httpbin.org/bearer");

        $this->assertEquals(200, $response->statusCode());
        $this->assertStringContainsString('authenticated', $response->getContent());
    }

    public function test_bearer_auth_fails_without_token()
    {
        $http = new HttpClient();

        $response = $http->get("https://httpbin.org/bearer");

        $this->assertEquals(401, $response->statusCode());
    }

    // ==================== Accept JSON Tests ====================

    public function test_accept_json_sets_content_type_header()
    {
        $http = new HttpClient();
        $http->acceptJson();

        $response = $http->post("https://httpbin.org/post", [
            'name' => 'test',
            'value' => 'example'
        ]);

        $this->assertEquals(200, $response->statusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('application/json', $content['headers']['Content-Type']);
    }

    // ==================== Timeout Configuration Tests ====================

    public function test_connect_timeout_configuration()
    {
        $http = new HttpClient();
        $http->connectTimeout(5);

        $response = $http->get("https://httpbin.org/get");

        $this->assertEquals(200, $response->statusCode());
    }

    public function test_timeout_configuration()
    {
        $http = new HttpClient();
        $http->timeout(10);

        $response = $http->get("https://httpbin.org/get");

        $this->assertEquals(200, $response->statusCode());
    }

    // ==================== SSL Verification Tests ====================

    public function test_disable_ssl_verification()
    {
        $http = new HttpClient();
        $http->disableSslVerification();

        $response = $http->get("https://httpbin.org/get");

        $this->assertEquals(200, $response->statusCode());
    }

    // ==================== Base URL Tests ====================

    public function test_set_base_url_method()
    {
        $http = new HttpClient();
        $http->setBaseUrl("https://httpbin.org");

        $response = $http->get("/get");

        $this->assertEquals(200, $response->statusCode());
    }

    // ==================== Method Chaining Tests ====================

    public function test_method_chaining()
    {
        $http = new HttpClient();

        $response = $http
            ->withHeaders(['X-Custom' => 'value'])
            ->acceptJson()
            ->timeout(10)
            ->connectTimeout(5)
            ->post("https://httpbin.org/post", ['key' => 'value']);

        $this->assertEquals(200, $response->statusCode());
    }
}
