<?php

declare(strict_types=1);

namespace Bow\Testing;

use BadMethodCallException;
use Bow\Http\Client\HttpClient;
use Exception;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    /**
     * The base url. If null, resolves to APP_URL env var, then to
     * http://127.0.0.1:8080 (the default of `php bow run:server`).
     *
     * @var ?string
     */
    protected ?string $url = null;

    /**
     * Attachments to send with the next request. Cleared after each call.
     *
     * @var array
     */
    private array $attach = [];

    /**
     * Headers applied to every request made by this test instance.
     * Use withHeader() / withHeaders() to populate. Persists until the
     * test ends or you reset it manually.
     *
     * @var array
     */
    private array $headers = [];

    /**
     * Add files / multipart attachments to the next request.
     * Cleared automatically after the request is sent.
     */
    public function attach(array $attach): TestCase
    {
        $this->attach = $attach;

        return $this;
    }

    /**
     * Replace the header map applied to every request.
     */
    public function withHeaders(array $headers): TestCase
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Add (or override) a single header.
     */
    public function withHeader(string $key, string $value): TestCase
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * GET request.
     *
     * @throws Exception
     */
    public function get(string $url, array $param = []): Response
    {
        return new Response($this->newHttpClient()->get($url, $param));
    }

    /**
     * POST request.
     *
     * @throws Exception
     */
    public function post(string $url, array $param = []): Response
    {
        return new Response($this->newHttpClient()->post($url, $param));
    }

    /**
     * PUT request.
     *
     * @throws Exception
     */
    public function put(string $url, array $param = []): Response
    {
        return new Response($this->newHttpClient()->put($url, $param));
    }

    /**
     * PATCH request (real HTTP PATCH — no _method POST hack).
     *
     * @throws Exception
     */
    public function patch(string $url, array $param = []): Response
    {
        return new Response($this->newHttpClient()->patch($url, $param));
    }

    /**
     * DELETE request (real HTTP DELETE — no _method POST hack).
     *
     * @throws Exception
     */
    public function delete(string $url, array $param = []): Response
    {
        return new Response($this->newHttpClient()->delete($url, $param));
    }

    /**
     * HEAD request (headers only, no body).
     *
     * @throws Exception
     */
    public function head(string $url, array $param = []): Response
    {
        return new Response($this->newHttpClient()->head($url, $param));
    }

    /**
     * OPTIONS request (typically for CORS preflight).
     *
     * @throws Exception
     */
    public function options(string $url): Response
    {
        return new Response($this->newHttpClient()->options($url));
    }

    /**
     * Dispatch a request by HTTP verb name.
     *
     * @throws BadMethodCallException
     */
    public function visit(string $method, string $url, array $params = []): Response
    {
        $method = strtolower($method);
        $allowed = ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'];

        if (!in_array($method, $allowed, true)) {
            throw new BadMethodCallException(
                'The HTTP [' . $method . '] method does not exists.'
            );
        }

        return $method === 'options'
            ? $this->options($url)
            : $this->$method($url, $params);
    }

    /**
     * Build a fresh HttpClient pre-configured with the current headers and
     * pending attachments. Attachments are consumed (reset) after this call;
     * headers persist for the lifetime of the test instance.
     */
    protected function newHttpClient(): HttpClient
    {
        $http = new HttpClient($this->getBaseUrl());

        if ($this->headers !== []) {
            $http->withHeaders($this->headers);
        }

        if ($this->attach !== []) {
            $http->addAttach($this->attach);
            $this->attach = []; // consume — don't leak into the next call
        }

        return $http;
    }

    /**
     * Resolve the base URL. Override this in a subclass for more elaborate
     * setups (per-test base URLs, computed from env, etc.).
     */
    protected function getBaseUrl(): string
    {
        return rtrim($this->url ?? app_env('APP_URL', 'http://127.0.0.1:8080'), '/');
    }
}
