<?php

namespace Bow\Http;

use Bow\Session\Session;

class Redirect
{
    /**
     * @var Request
     */
    private $request;

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
     * redirect, permet de lancer une redirection vers l'url passé en paramêtre
     *
     * @param string|array $path L'url de rédirection
     * @param $status $path L'url de rédirection
     *
     *  Si $path est un tableau :
     *
     * 	$url = [
     * 		'url' => '//'
     * 		'?' => [
     * 			'name' => 'dakia',
     * 			'lastname' => 'franck',
     * 			'id' => '1',
     * 		],
     * 		'#' => 'hello',
     *      'data' => [] // des données a récupéré dans la page de rédirection
     * ];
     *
     */
    public function to($path, $status = 302)
    {
        if (is_string($path)) {
            $path = ['url' => $path];
        }

        $url = $path['url'];

        if (isset($path['?'])) {
            $url .= '?';
            foreach($path['?'] as $key => $value) {
                if ($key > 0) {
                    $url .= '&';
                }
                $url .= $key . '=' . $value;
            }
        }

        if (isset($path['#'])) {
            $url .= '#' . $path['#'];
        }

        $this->response->statusCode($status);
        header('Location: ' . $url);
        exit;
    }

    /**
     * @param array $data
     */
    public function withInput(array $data)
    {
        Session::add('__bow.old', $data);
    }

    /**
     * Permet de faire une rédirection sur l'url précédent
     *
     * @param int $status
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
}