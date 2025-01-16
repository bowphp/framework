<?php

declare(strict_types=1);

namespace Bow\Testing;

use Bow\Http\Client\HttpClient;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    /**
     * The request attachment collection
     *
     * @var array
     */
    private array $attach = [];

    /**
     * The base url
     *
     * @var ?string
     */
    protected ?string $url = null;

    /**
     * The list of additional header
     *
     * @var array
     */
    private array $headers = [];

    /**
     * Get the base url
     *
     * @return string
     */
    private function getBaseUrl(): string
    {
        return $this->url ?? rtrim(app_env('APP_URL', 'http://127.0.0.1:5000'));
    }

    /**
     * Add attachment
     *
     * @param array $attach
     * @return TestCase
     */
    public function attach(array $attach): TestCase
    {
        $this->attach = $attach;

        return $this;
    }

    /**
     * Specify the additional headers
     *
     * @param array $headers
     * @return TestCase
     */
    public function withHeaders(array $headers): TestCase
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Specify the additional header
     *
     * @param string $key
     * @param string $value
     * @return TestCase
     */
    public function withHeader(string $key, string $value): TestCase
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * Get request
     *
     * @param string $url
     * @param array $param
     * @return Response
     * @throws \Exception
     */
    public function get(string $url, array $param = []): Response
    {
        $http = new HttpClient($this->getBaseUrl());

        $http->addHeaders($this->headers);

        return new Response($http->get($url, $param));
    }

    /**
     * Post Request
     *
     * @param string $url
     * @param array $param
     * @return Response
     * @throws \Exception
     */
    public function post(string $url, array $param = []): Response
    {
        $http = new HttpClient($this->getBaseUrl());

        if (!empty($this->attach)) {
            $http->addAttach($this->attach);
        }

        $http->addHeaders($this->headers);

        return new Response($http->post($url, $param));
    }

    /**
     * Put Request
     *
     * @param string $url
     * @param array $param
     * @return Response
     * @throws \Exception
     */
    public function put(string $url, array $param = []): Response
    {
        $http = new HttpClient($this->getBaseUrl());

        $http->addHeaders($this->headers);

        return new Response($http->put($url, $param));
    }

    /**
     * Delete Request
     *
     * @param string $url
     * @param array $param
     * @return Response
     * @throws \Exception
     */
    public function delete(string $url, array $param = []): Response
    {
        $param = array_merge([
            '_method' => 'DELETE'
        ], $param);

        return $this->put($url, $param);
    }

    /**
     * Patch Request
     *
     * @param string $url
     * @param array $param
     * @return Response
     * @throws \Exception
     */
    public function patch(string $url, array $param = []): Response
    {
        $param = array_merge([
            '_method' => 'PATCH'
        ], $param);

        return $this->put($url, $param);
    }

    /**
     * Initialize Response action
     *
     * @param string $method
     * @param string $url
     * @param array  $params
     * @return Response
     */
    public function visit(string $method, string $url, array $params = []): Response
    {
        $method = strtolower($method);

        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException(
                'The HTTP [' . $method . '] method does not exists.'
            );
        }

        return $this->$method($url, $params);
    }
}
