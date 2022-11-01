<?php

declare(strict_types=1);

namespace Bow\Testing;

use Bow\Http\Client\HttpClient;
use Bow\Http\Client\Parser;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends \PHPUnitTestCase
{
    /**
     * The request attachment collection
     *
     * @var array
     */
    private $attach = [];

    /**
     * The base url
     *
     * @var string
     */
    protected $url;

    /**
     * The list of additionnal header
     *
     * @var array
     */
    private $headers = [];

    /**
     * Format url
     *
     * @param  $url
     * @return string
     */
    private function formatUrl($url)
    {
        if (!$this->url) {
            $this->url = app_env('APP_URL', 'http://localhost:5000');
        }
        return rtrim($this->url, '/').$url;
    }

    /**
     * Add attachment
     *
     * @param array $attach
     * @return Response
     */
    public function attach(array $attach)
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
    public function withHeader(array $headers)
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
    public function get($url, array $param = [])
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
    public function post($url, array $param = [])
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
    public function put($url, array $param = [])
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
    public function delete($url, array $param = [])
    {
        $param = array_merge([
            '_method' => 'DELETE'
        ], $param);

        return new Response($this->put($url, $param));
    }

    /**
     * Patch Request
     *
     * @param string $url
     * @param array $param
     * @return Response
     */
    public function patch($url, array $param = [])
    {
        $param = array_merge([
            '_method' => 'PATCH'
        ], $param);

        return new Response($this->put($url, $param));
    }

    /**
     * Initilalize Response action
     *
     * @param string $method
     * @param string $url
     * @param array  $params
     * @return Response
     */
    public function visit($method, $url, array $params = [])
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
