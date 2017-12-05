<?php
namespace Bow\Testing;

use Bow\Http\Client\Parser;
use Bow\Http\Client\HttpClient;
use PHPUnit\Framework\TestCase;

class BowTestCase extends TestCase
{
    /**
     * @var array
     */
    private $_attach = [];

    /**
     * @var string
     */
    protected $base_url = '';

    /**
     * Format url
     *
     * @param  $url
     * @return string
     */
    private function formatUrl($url)
    {
        return rtrim($this->base_url, '/').$url;
    }

    /**
     * @param $url
     * @param $param
     * @return Parser
     */
    public function get($url, array $param = [])
    {
        $http = new HttpClient($this->formatUrl($url));
        return $http->get($url, $param);
    }

    /**
     * @param $url
     * @param $param
     * @return Parser
     */
    public function post($url, array $param = [])
    {
        $http = new HttpClient($this->formatUrl($url));

        if (!empty($this->_attach)) {
            $http->addAttach($this->_attach);
        }

        return $http->post($url, $param);
    }

    /**
     * @param array $attach
     */
    public function attach(array $attach)
    {
        $this->_attach = $attach;
    }

    /**
     * @param $url
     * @param $param
     * @return Parser
     */
    public function put($url, array $param = [])
    {
        $http = new HttpClient($this->formatUrl($url));

        return $http->put($url, $param);
    }

    /**
     * @param $url
     * @param array $param
     * @return Parser
     */
    public function delete($url, array $param = [])
    {
        $param = array_merge(
            [
            '_method' => 'DELETE'
            ],
            $param
        );

        return $this->put($url, $param);
    }

    /**
     * @param $url
     * @param $param
     * @return Parser
     */
    public function patch($url, array $param = [])
    {
        $param = array_merge(
            [
            '_method' => 'PATCH'
            ],
            $param
        );

        return $this->put($url, $param);
    }

    /**
     * @param $method
     * @param $url
     * @param array  $params
     * @return Behavior
     */
    public function visit($method, $url, array $params = [])
    {
        $method = strtolower($method);

        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException('La methode ' . $method . ' n\'exist pas');
        }

        return new Behavior($this->$method($url, $params));
    }
}
