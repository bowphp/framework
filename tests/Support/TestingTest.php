<?php

namespace Bow\Tests\Support;

use Bow\Testing\TestCase;

class TestingTest extends TestCase
{
    /**
     * The base url
     *
     * @var string
     */
    protected ?string $url = "https://google.com";

    public function test_get_method()
    {
        $response = $this->get("/");

        $response->assertStatus(200);
    }

    public function test_get_method_with_custom_headers()
    {
        $this->withHeaders(["X-Api-Key" => "Fake-Key"]);

        $response = $this->get("/");

        $response->assertStatus(200);
    }

    public function test_should_be_fail_with_get_method()
    {
        $this->withHeaders(["X-Api-Key" => "Fake-Key"]);

        $response = $this->get("/the-fake-url-for-my-testing-please-do-not-block-this");

        $response->assertStatus(404);
    }
}
