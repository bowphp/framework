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

        if (!is_null($base_url)) {
            $this->base_url = rtrim($base_url, "/");
        }
    }

    /**
     * Set the base url
     *
     * @param string $url
     * @return void
     */
    public function setBaseUrl(string $url): void
    {
        $this->base_url = rtrim($url, "/");
    }

    /**
     * Make get requete
     *
     * @param string $url
     * @param array $data
     * @return Response
     */
    public function get(string $url, array $data = []): Response
    {
        if (count($data) > 0) {
            $params = http_build_query($data);
            $url . "?" . $params;
        }

        $this->init($url);

        curl_setopt($this->ch, CURLOPT_HTTPGET, true);

        return new Response($this->ch);
    }

    /**
     * make post requete
     *
     * @param string $url
     * @param array $data
     * @return Response
     */
    public function post(string $url, array $data = []): Response
    {
        $this->init($url);

        if (!empty($this->attach)) {
            curl_setopt($this->ch, CURLOPT_UPLOAD, true);

            foreach ($this->attach as $key => $attach) {
                $this->attach[$key] = '@' . ltrim('@', $attach);
            }

            $data = array_merge($this->attach, $data);
        }

        curl_setopt($this->ch, CURLOPT_POST, true);

        $this->addFields($data);

        return new Response($this->ch);
    }

    /**
     * Make put requete
     *
     * @param string $url
     * @param array $data
     * @return Response
     */
    public function put(string $url, array $data = []): Response
    {
        $this->init($url);

        if (!curl_setopt($this->ch, CURLOPT_PUT, true)) {
            $this->addFields($data);
        }

        curl_setopt($this->ch, CURLOPT_PUT, true);

        return new Response($this->ch);
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
    private function init(string $url): void
    {
        if (!is_null($this->base_url)) {
            $url = $this->base_url . "/" . trim($url, "/");
        }

        $this->ch = curl_init(trim($url, "/"));
    }

    /**
     * Add fields
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
