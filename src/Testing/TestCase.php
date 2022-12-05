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
     * @var string
     */
    protected ?string $url = null;

    /**
     * The list of additionnal header
     *
     * @var array
     */
    private array $headers = [];

    /**
     * Format url
     *
     * @param  $url
     * @return string
     */
    private function formatUrl(string $url): string
    {
        if (!$this->url) {
            $this->url = app_env('APP_URL', 'http://127.0.0.1:5000');
        }

        $url = rtrim($this->url, '/') . $url;

        return trim($url, '/');
    }

    /**
     * Add attachment
     *
     * @param array $attach
     * @return Response
     */
    public function attach(array $attach): TestCase
    {
        $this->attach = $attach;

        return $this;
    }

    /**
     * Specify the additionnal who are use in the request
     *
     * @param array $headers
     * @return TestCase
     */
    public function withHeader(array $headers): TestCase
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Get request
     *
     * @param string $url
     * @param array $param
     * @return Response
     */
    public function get(string $url, array $param = []): Response
    {
        $http = new HttpClient($this->formatUrl($url));

        $http->addHeaders($this->headers);

        return new Response($http->get($url, $param));
    }

    /**
     * Post Request
     *
     * @param string $url
     * @param array $param
     * @return Response
     */
    public function post(string $url, array $param = []): Response
    {
        $http = new HttpClient($this->formatUrl($url));

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
     */
    public function put(string $url, array $param = []): Response
    {
        $http = new HttpClient($this->formatUrl($url));

        $http->addHeaders($this->headers);

        return new Response($http->put($url, $param));
    }

    /**
     * Delete Request
     *
     * @param string $url
     * @param array $param
     * @return Response
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
     */
    public function patch(string $url, array $param = [])
    {
        $param = array_merge([
            '_method' => 'PATCH'
        ], $param);

        return $this->put($url, $param);
    }

    /**
     * Initilalize Response action
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
