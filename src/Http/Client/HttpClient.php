<?php

namespace Bow\Http\Client;

use function array_merge;
use function curl_setopt;
use const CURLOPT_FILE;
use function ltrim;

class HttpClient
{
    /**
     * @var array
     */
    private $attach = [];

    /**
     * @var Resource
     */
    private $ch;

    /**
     * @var string
     */
    private $url;

    /**
     * Constructeur d'instance.
     *
     * @param string $url
     */
    public function __construct($url = null)
    {
        if (!function_exists('curl_init')) {
            throw new \BadFunctionCallException('Installer la librairie cURL de php.');
        }

        if (is_string($url)) {
            $this->ch = curl_init($url);
            $this->url = $url;
        }
    }

    /**
     * Make get requete
     *
     * @param  string $url
     * @param  array  $data
     * @return Parser
     */
    public function get($url, array $data = [])
    {
        $this->resetAndAssociateUrl($url);
        $this->addFields($data);

        return new Parser($this->ch);
    }

    /**
     * make post requete
     *
     * @param  string $url
     * @param  array  $data
     * @return Parser
     */
    public function post($url, array $data = [])
    {
        $this->resetAndAssociateUrl($url);

        if (!curl_setopt($this->ch, CURLOPT_POST, true)) {
            if (!empty($this->attach)) {
                curl_setopt($this->ch, CURLOPT_SAFE_UPLOAD, true);
                foreach ($this->attach as $key => $attach) {
                    $this->attach[$key] = '@'.ltrim('@', $attach);
                }
                $data = array_merge($this->attach, $data);
            }

            $this->addFields($data);
        }

        return new Parser($this->ch);
    }

    /**
     * make put requete
     *
     * @param  string $url
     * @param  array  $data
     * @return Parser
     */
    public function put($url, array $data = [])
    {
        $this->resetAndAssociateUrl($url);

        if (!curl_setopt($this->ch, CURLOPT_PUT, true)) {
            $this->addFields($data);
        }

        return new Parser($this->ch);
    }

    /**
     * @param $attach
     * @return $this
     */
    public function addAttach($attach)
    {
        return $this->attach = $attach;
    }

    /**
     * Reset alway connection
     *
     * @param string $url
     */
    private function resetAndAssociateUrl($url)
    {
        if (!is_resource($this->ch)) {
            $this->ch = curl_init($url);
        }
    }

    /**
     * @param array $data
     */
    private function addFields(array $data)
    {
        if (!empty($data)) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }
}
