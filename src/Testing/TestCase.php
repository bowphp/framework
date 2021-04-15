<?php

namespace Bow\Testing;

use Bow\Http\Client\HttpClient;
use Bow\Http\Client\Parser;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
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
     * @return Behavior
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
     * @return Behavior
     */
    public function get($url, array $param = [])
    {
        $http = new HttpClient($this->formatUrl($url));

        $http->addHeaders($this->headers);

        return new Behavior($http->get($url, $param));
    }

    /**
     * Post Request
     *
     * @param string $url
     * @param array $param
     * @return Behavior
     */
    public function post($url, array $param = [])
    {
        $http = new HttpClient($this->formatUrl($url));

        if (!empty($this->attach)) {
            $http->addAttach($this->attach);
        }

        $http->addHeaders($this->headers);

        return new Behavior($http->post($url, $param));
    }

    /**
     * Put Request
     *
     * @param string $url
     * @param array $param
     * @return Behavior
     */
    public function put($url, array $param = [])
    {
        $http = new HttpClient($this->formatUrl($url));

        $http->addHeaders($this->headers);

        return new Behavior($http->put($url, $param));
    }

    /**
     * Delete Request
     *
     * @param string $url
     * @param array $param
     * @return Behavior
     */
    public function delete($url, array $param = [])
    {
        $param = array_merge([
            '_method' => 'DELETE'
        ], $param);

        return new Behavior($this->put($url, $param));
    }

    /**
     * Patch Request
     *
     * @param string $url
     * @param array $param
     * @return Behavior
     */
    public function patch($url, array $param = [])
    {
        $param = array_merge([
            '_method' => 'PATCH'
        ], $param);

        return new Behavior($this->put($url, $param));
    }

    /**
     * Initilalize Behavior action
     *
     * @param string $method
     * @param string $url
     * @param array  $params
     * @return Behavior
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
