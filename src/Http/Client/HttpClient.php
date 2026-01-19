<?php

declare(strict_types=1);

namespace Bow\Http\Client;

use BadFunctionCallException;
use CurlHandle;
use Exception;

class HttpClient
{
    /**
     * The attached file collection
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
    private array $headers = [];

    /**
     * The curl instance
     *
     * @var ?CurlHandle
     */
    private ?CurlHandle $ch = null;

    /**
     * The base url
     *
     * @var string|null
     */
    private ?string $base_url = null;

    /**
     * The request timeout in seconds
     *
     * @var int|null
     */
    private ?int $timeout = null;

    /**
     * The connection timeout in seconds
     *
     * @var int|null
     */
    private ?int $connect_timeout = null;

    /**
     * Whether to verify SSL certificates
     *
     * @var bool
     */
    private bool $verify_ssl = true;

    /**
     * HttpClient Constructor.
     *
     * @param string|null $base_url
     */
    public function __construct(?string $base_url = null)
    {
        if (!function_exists('curl_init')) {
            throw new BadFunctionCallException('cURL extension is required.');
        }

        if (!is_null($base_url)) {
            $this->base_url = rtrim($base_url, "/");
        }
    }

    /**
     * Set the base url
     *
     * @param  string $url
     * @return void
     */
    public function setBaseUrl(string $url): void
    {
        $this->base_url = rtrim($url, "/");
    }

    /**
     * Make GET request
     *
     * @param  string $url
     * @param  array  $data
     * @return Response
     * @throws Exception
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
     * Initialize connection with URL
     *
     * @param  string $url
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
     * Apply common cURL options
     *
     * @return void
     */
    private function applyCommonOptions(): void
    {
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_AUTOREFERER, true);

        if ($this->timeout !== null) {
            curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
        }

        if ($this->connect_timeout !== null) {
            curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
        }

        if (!$this->verify_ssl) {
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
        }
    }

    /**
     * Execute request
     *
     * @return string
     * @throws Exception
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
     * Close connection
     *
     * @return void
     */
    private function close(): void
    {
        curl_close($this->ch);
    }

    /**
     * Make POST request
     *
     * @param  string $url
     * @param  array  $data
     * @return Response
     * @throws Exception
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
     * Add fields
     *
     * @param  array $data
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
     * Make PUT request
     *
     * @param  string $url
     * @param  array  $data
     * @return Response
     * @throws Exception
     */
    public function put(string $url, array $data = []): Response
    {
        $this->init($url);
        $this->addFields($data);
        $this->applyCommonOptions();

        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "PUT");

        $content = $this->execute();

        return new Response($this->ch, $content);
    }

    /**
     * Make DELETE request
     *
     * @param  string $url
     * @param  array  $data
     * @return Response
     * @throws Exception
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
     * Attach file(s) to the request
     *
     * @param  string|array $attach
     * @return HttpClient
     */
    public function addAttach(string|array $attach): HttpClient
    {
        $this->attach = (array)$attach;

        return $this;
    }

    /**
     * Set the User-Agent header
     *
     * @param  string $user_agent
     * @return HttpClient
     */
    public function setUserAgent(string $user_agent): HttpClient
    {
        curl_setopt($this->ch, CURLOPT_USERAGENT, $user_agent);

        return $this;
    }

    /**
     * Configure client to accept and send JSON data
     *
     * @return HttpClient
     */
    public function acceptJson(): HttpClient
    {
        $this->accept_json = true;

        $this->withHeaders(["Content-Type" => "application/json"]);

        return $this;
    }

    /**
     * Add custom HTTP headers
     *
     * @param  array $headers
     * @return HttpClient
     */
    public function withHeaders(array $headers): HttpClient
    {
        foreach ($headers as $key => $value) {
            if (!in_array(strtolower($key . ': ' . $value), array_map('strtolower', $this->headers))) {
                $this->headers[] = $key . ': ' . $value;
            }
        }

        return $this;
    }

    /**
     * Set HTTP authentication credentials
     *
     * @param  string $username
     * @param  string $password
     * @return HttpClient
     */
    public function auth(string $username, string $password): HttpClient
    {
        curl_setopt($this->ch, CURLOPT_USERPWD, $username . ":" . $password);

        return $this;
    }

    /**
     * Set Basic HTTP authentication
     *
     * @param  string $key
     * @param  string $secret
     * @return HttpClient
     */
    public function basicAuth(string $key, string $secret): HttpClient
    {
        $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode($key . ':' . $secret)
        ]);

        return $this;
    }

    /**
     * Set Bearer token authentication
     *
     * @param  string $token
     * @return HttpClient
     */
    public function bearerAuth(string $token): HttpClient
    {
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ]);

        return $this;
    }

    /**
     * Set the maximum time the request is allowed to take
     *
     * @param  int $seconds
     * @return HttpClient
     */
    public function timeout(int $seconds): HttpClient
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Set the maximum time to wait for a connection
     *
     * @param  int $seconds
     * @return HttpClient
     */
    public function connectTimeout(int $seconds): HttpClient
    {
        $this->connect_timeout = $seconds;

        return $this;
    }

    /**
     * Disable SSL certificate verification
     *
     * Warning: This should only be used in development environments
     *
     * @return HttpClient
     */
    public function disableSslVerification(): HttpClient
    {
        $this->verify_ssl = false;

        return $this;
    }
}
