<?php

declare(strict_types=1);

namespace Bow\Http;

use Bow\Contracts\ResponseInterface;
use Bow\Session\Session;

class Redirect implements ResponseInterface
{
    /**
     * The Request instance
     *
     * @var Request
     */
    private $request;

    /**
     * The redirect targets
     *
     * @var string
     */
    private $to;

    /**
     * The Response instance
     *
     * @var Response
     */
    private $response;

    /**
     * The Redirect instance
     *
     * @var Redirect
     */
    private static $instance;

    /**
     * Redirect constructor.
     *
     * @return void
     */
    private function __construct()
    {
        $this->request = Request::getInstance();

        $this->response = Response::getInstance();
    }

    /**
     * Get redirection instance
     *
     * @return Redirect
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new Redirect();
        }

        return static::$instance;
    }

    /**
     * Redirection with the query information
     *
     * @param array $data
     * @return Redirect
     */
    public function withInput(array $data = [])
    {
        if (count($data) == 0) {
            $this->request->session()->add('__bow.old', $this->request->all());
        } else {
            $this->request->session()->add('__bow.old', $data);
        }

        return $this;
    }

    /**
     * Redirection with define flash information
     *
     * @param string $key
     * @param mixed $value
     * @return Redirect
     */
    public function withFlash($key, $value)
    {
        $this->request->session()->flash($key, $value);

        return $this;
    }

    /**
     * Redirect to another URL
     *
     * @param string $path
     * @param int $status
     * @return Redirect
     */
    public function to($path, $status = 302)
    {
        $this->to = $path;

        $this->response->status($status);

        return $this;
    }

    /**
     * Redirect with route definition
     *
     * @param  string $name
     * @param  array  $data
     * @param  bool  $absolute
     * @return Redirect
     */
    public function route($name, $data = [], $absolute = false)
    {
        $this->to = route($name, $data, $absolute);

        return $this;
    }

    /**
     * Redirect on the previous URL
     *
     * @param int $status
     * @return Redirect
     */
    public function back($status = 302)
    {
        $this->to($this->request->referer(), $status);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function sendContent()
    {
        $this->response->addHeader('Location', $this->to);

        return $this->response->sendContent();
    }

    /**
     * __invoke
     *
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func_array([$this, 'to'], func_get_args());
    }

    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {
        return $this->to;
    }
}
