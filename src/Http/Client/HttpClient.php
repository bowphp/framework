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
    private array $attach = [];

    /**
     * Define the accept json header
     *
     * @var boolean
     */
    private bool $accept_json = false;

    /**
     * The headers collection
     *
     * @var array
     */
    private $headers = [];

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
            $url = $url . "?" . http_build_query($data);
        }

        $this->init($url);
        $this->applyCommonOptions();

        curl_setopt($this->ch, CURLOPT_HTTPGET, true);

        $content = $this->execute();

        return new Response($this->ch, $content);
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

        $this->addFields($data);
        $this->applyCommonOptions();

        curl_setopt($this->ch, CURLOPT_POST, true);

        $content = $this->execute();

        return new Response($this->ch, $content);
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
        $this->addFields($data);
        $this->applyCommonOptions();

        curl_setopt($this->ch, CURLOPT_PUT, true);

        $content = $this->execute();

        return new Response($this->ch, $content);
    }

    /**
     * Make put requete
     *
     * @param string $url
     * @param array $data
     * @return Response
     */
    public function delete(string $url, array $data = []): Response
    {
        $this->init($url);
        $this->addFields($data);
        $this->applyCommonOptions();

        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "DELETE");

        $content = $this->execute();

        return new Response($this->ch, $content);
    }

    /**
     * Attach new file
     *
     * @param string $attach
     * @return HttpClient
     */
    public function addAttach(string|array $attach): HttpClient
    {
        $this->attach = (array) $attach;

        return $this;
    }

    /**
     * Add aditionnal header
     *
     * @param array $headers
     * @return HttpClient
     */
    public function addHeaders(array $headers): HttpClient
    {
        foreach ($headers as $key => $value) {
            if (!in_array(strtolower($key . ': ' . $value), array_map('strtolower', $this->headers))) {
                $this->headers[] = $key . ': ' . $value;
            }
        }

        return $this;
    }

    /**
     * Set the user agent
     *
     * @param string $user_agent
     * @return HttpClient
     */
    public function setUserAgent(string $user_agent): HttpClient
    {
        curl_setopt($this->ch, CURLOPT_USERAGENT, $user_agent);

        return $this;
    }

    /**
     * Set the json accept prop to format the sent content in json
     *
     * @return HttpClient
     */
    public function acceptJson(): HttpClient
    {
        $this->accept_json = true;

        $this->addHeaders(["Content-Type" => "application/json"]);

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
        if (count($data) == 0) {
            return;
        }

        if ($this->accept_json) {
            $payload = json_encode($data);
        } else {
            $payload = http_build_query($data);
        }

        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $payload);
    }

    /**
     * Close connection
     *
     * @return void
     */
    private function close(): void
    {
        curl_close($this->ch);
    }

    /**
     * Execute request
     *
     * @return string
     * @throws \Exception
     */
    private function execute(): string
    {
        if ($this->headers) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->headers);
        }

        $content = curl_exec($this->ch);
        $errno = curl_errno($this->ch);

        $this->close();

        if ($content === false) {
            throw new HttpClientException(
                curl_strerror($errno),
                $errno
            );
        }

        return $content;
    }

    /**
     * Set Curl CURLOPT_RETURNTRANSFER option
     *
     * @return void
     */
    private function applyCommonOptions()
    {
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_AUTOREFERER, true);
    }
}
