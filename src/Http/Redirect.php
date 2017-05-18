<?php

namespace Bow\Http;

class Redirect
{
    /**
     * redirect, permet de lancer une redirection vers l'url passé en paramêtre
     *
     * @param string|array $path L'url de rédirection
     * @param array $data des données a récupéré dans la page de rédirection
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
    public function to($path, array $data = [])
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

        if (empty($data)) {
            if (isset($path['data'])) {
                $data = $path['data'];
            }
        }

        header('Location: ' . $url);
        exit;
    }

    /**
     * Permet de rédiriger vers 404
     *
     * @return self
     */
    public function to404()
    {
        Response::instance()->code(404);

        return $this;
    }

    /**
     * Permet de faire une rédirection sur l'url précédent
     *
     * @param array $data
     */
    public function back(array $data = [])
    {
        $referer = Request::instance()->referer();

        $this->to($referer, $data);
    }
}