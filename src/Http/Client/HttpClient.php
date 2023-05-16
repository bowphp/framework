<?php

declare(strict_types=1);

namespace Bow\Http\Client;

use CurlHandle;

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
     * @var CurlHandle
     */
    private ?CurlHandle $ch = null;

    /**
     * The base url
     *
     * @var string|null
     */
    private ?string $base_url = null;

    /**
     * HttpClient Constructor.
     *
     * @param string $base_url
     * @return void
     */
    public function __construct(?string $base_url = null)
    {
        if (!function_exists('curl_init')) {
            throw new \BadFunctionCallException('cURL php is require.');
        }

        $this->base_url = rtrim($base_url, "/");
    }

    /**
     * Make get requete
     *
     * @param string $url
     * @param array $data
     * @return Parser
     */
    public function get(string $url, array $data = []): Parser
    {
        $this->initCurl($url);

        $this->addFields($data);

        curl_setopt($this->ch, CURLOPT_HTTPGET, true);

        return new Parser($this->ch);
    }

    /**
     * make post requete
     *
     * @param string $url
     * @param array $data
     * @return Parser
     */
    public function post(string $url, array $data = []): Parser
    {
        $this->initCurl($url);

        if (!empty($this->attach)) {
            curl_setopt($this->ch, CURLOPT_UPLOAD, true);

            foreach ($this->attach as $key => $attach) {
                $this->attach[$key] = '@' . ltrim('@', $attach);
            }

            $data = array_merge($this->attach, $data);
        }

        curl_setopt($this->ch, CURLOPT_POST, true);
        $this->addFields($data);

        return new Parser($this->ch);
    }

    /**
     * Make put requete
     *
     * @param string $url
     * @param array $data
     * @return Parser
     */
    public function put(string $url, array $data = []): Parser
    {
        $this->initCurl($url);

        if (!curl_setopt($this->ch, CURLOPT_PUT, true)) {
            $this->addFields($data);
        }

        curl_setopt($this->ch, CURLOPT_PUT, true);

        return new Parser($this->ch);
    }

    /**
     * Attach new file
     *
     * @param string $attach
     * @return array
     */
    public function addAttach(string|array $attach): array
    {
        return $this->attach = (array) $attach;
    }

    /**
     * Add aditionnal header
     *
     * @param array $headers
     * @return HttpClient
     */
    public function addHeaders(array $headers): HttpClient
    {
        if (is_resource($this->ch)) {
            $data = [];

            foreach ($headers as $key => $value) {
                $data[] = $key . ': ' . $value;
            }

            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $data);
        }

        return $this;
    }

    /**
     * Reset alway connection
     *
     * @param string $url
     * @return void
     */
    private function initCurl(string $url): void
    {
        $url = $this->base_url . "/" . trim($url, "/");

        $this->ch = curl_init($url);
    }

    /**
     * Add field
     *
     * @param array $data
     * @return void
     */
    private function addFields(array $data): void
    {
        if (count($data) > 0) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }
}
