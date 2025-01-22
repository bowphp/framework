<?php

declare(strict_types=1);

namespace Bow\Http;

use Bow\Contracts\ResponseInterface;

class Redirect implements ResponseInterface
{
    /**
     * The Redirect instance
     *
     * @var ?Redirect
     */
    private static ?Redirect $instance = null;
    /**
     * The Request instance
     *
     * @var Request
     */
    private Request $request;
    /**
     * The redirect targets
     *
     * @var string
     */
    private string $to;
    /**
     * The Response instance
     *
     * @var Response
     */
    private Response $response;

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
    public static function getInstance(): Redirect
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
    public function withInput(array $data = []): Redirect
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
    public function withFlash(string $key, mixed $value): Redirect
    {
        $this->request->session()->flash($key, $value);

        return $this;
    }

    /**
     * Redirect with route definition
     *
     * @param string $name
     * @param array $data
     * @param bool $absolute
     * @return Redirect
     */
    public function route(string $name, array $data = [], bool $absolute = false): Redirect
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
    public function back(int $status = 302): Redirect
    {
        $this->to($this->request->referer(), $status);

        return $this;
    }

    /**
     * Redirect to another URL
     *
     * @param string $path
     * @param int $status
     * @return Redirect
     */
    public function to(string $path, int $status = 302): Redirect
    {
        $this->to = $path;

        $this->response->status($status);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function sendContent(): void
    {
        $this->response->addHeader('Location', $this->to);

        $this->response->sendContent();
    }

    /**
     * __invoke
     *
     * @return mixed
     */
    public function __invoke(): mixed
    {
        return call_user_func_array([$this, 'to'], func_get_args());
    }

    /**
     * __toString
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->to;
    }
}
