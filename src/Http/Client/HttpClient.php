<?php

namespace Bow\Http\Client;

class HttpClient
{
    /**
     * The attach file collection
     *
     * @var array
     */
    private $attach = [];

    /**
     * The curl instance
     *
     * @var Resource
     */
    private $ch;

    /**
     * The base url
     *
     * @var string
     */
    private $url;

    /**
     * HttpClient Constructor.
     *
     * @param string $url
     *
     * @return void
     */
    public function __construct($url = null)
    {
        if (!function_exists('curl_init')) {
            throw new \BadFunctionCallException('cURL php is require.');
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
     *
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
     * Make put requete
     *
     * @param  string $url
     * @param  array  $data
     *
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
     * Attach new file
     *
     * @param string $attach
     *
     * @return mixed
     */
    public function addAttach($attach)
    {
        return $this->attach = $attach;
    }

    /**
     * Add aditionnal header
     *
     * @param array $headers
     * @return HttpClient
     */
    public function addHeaders(array $headers)
    {
        if (is_resource($this->ch)) {
            $data = [];

            foreach ($headers as $key => $value) {
                $data[] = $key.': '.$value;
            }

            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $data);
        }

        return $this;
    }

    /**
     * Reset alway connection
     *
     * @param string $url
     *
     * @return void
     */
    private function resetAndAssociateUrl($url)
    {
        if (!is_resource($this->ch)) {
            $this->ch = curl_init(urlencode($url));
        }
    }

    /**
     * Add field
     *
     * @param array $data
     *
     * @return void
     */
    private function addFields(array $data)
    {
        if (!empty($data)) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }
}
