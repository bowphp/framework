<?php

namespace Bow\Http;

class Redirect
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
     * Redirect constructor.
     */
    public function __construct()
    {
        $this->request = Request::singleton();

        $this->response = Response::singleton();
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
     * @param array $data
     * @return static
     */
    public function withInput(array $data)
    {
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
}
