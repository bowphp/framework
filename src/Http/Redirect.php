<?php

namespace Bow\Http;

use Bow\Contrats\ResponseInterface;

class Redirect implements ResponseInterface
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var string
     */
    private $to;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Redirect
     */
    private static $instance;

    /**
     * Redirect constructor.
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
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @param $path
     * @param int $status
     * @return static
     */
    public function to($path, $status = 302)
    {
        $this->to = $path;

        $this->response->statusCode($status);

        return $this;
    }

    /**
     * Ajoute
     *
     * @param array $data
     * @return static
     */
    public function withInput(array $data = [])
    {
        if (count($data) == 0) {
            $this->request->session()->add('__bow.old', $this->request->all());

            return $this;
        }

        $this->request->session()->add('__bow.old', $data);

        return $this;
    }

    /**
     * Permet de faire une rédirection sur l'url précédent
     *
     * @param int   $status
     * @param array $data
     */
    public function back($status = 302, array $data = [])
    {
        $this->withInput($data);

        $this->to($this->request->referer(), $status);
    }

    /**
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func_array([$this, 'to'], func_get_args());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->to;
    }

    /**
     * @inheritdoc
     */
    public function send()
    {
        $this->response->addHeader('Location', $this->to)->statusCode(301);

        die();
    }
}
