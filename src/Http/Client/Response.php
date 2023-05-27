<?php

declare(strict_types=1);

namespace Bow\Http\Client;

use CurlHandle;

class Response
{
    /**
     * The error message
     *
     * @var string
     */
    private ?string $error_message = null;

    /**
     * The error number
     *
     * @var int
     */
    private int $errer_number;

    /**
     * The headers
     *
     * @var array
     */
    private array $headers = [];

    /**
     * Define the request content
     *
     * @var string|null
     */
    public ?string $content = null;

    /**
     * Parser constructor.
     *
     * @param CurlHandle $ch
     * @param ?string $content
     */
    public function __construct(CurlHandle &$ch, ?string $content = null)
    {
        $this->error_message = curl_error($ch);
        $this->errer_number = curl_errno($ch);
        $this->headers = curl_getinfo($ch);
        $this->content = $content;
    }

    /**
     * Get response content
     *
     * @return ?string
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Get response content as json
     *
     * @return object|array
     */
    public function toJson(): object|array
    {
        $content = $this->getContent();

        return json_decode($content);
    }

    /**
     * Get response content as json
     *
     * @return array
     */
    public function toArray(): array
    {
        $content = $this->getContent();

        return json_decode($content, true);
    }

    /**
     * Get the response headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get the response code
     *
     * @return ?int
     */
    public function getCode(): ?int
    {
        return $this->headers['http_code'] ?? null;
    }

    /**
     * Alias of getCode
     *
     * @return ?int
     */
    public function statusCode(): ?int
    {
        return $this->getCode();
    }

    /**
     * Get the response executing time
     *
     * @return ?int
     */
    public function getExecutionTime(): ?int
    {
        return $this->headers['total_time'] ?? null;
    }

    /**
     * Get the request connexion time
     *
     * @return ?float
     */
    public function getConnexionTime(): ?float
    {
        return $this->headers['connect_time'] ?? null;
    }

    /**
     * Get the response upload size
     *
     * @return ?float
     */
    public function getUploadSize(): ?float
    {
        return $this->headers['size_upload'] ?? null;
    }

    /**
     * Get the request upload speed
     *
     * @return ?float
     */
    public function getUploadSpeed(): ?float
    {
        return $this->headers['speed_upload'] ?? null;
    }

    /**
     * Get the download size
     *
     * @return ?float
     */
    public function getDownloadSize(): ?float
    {
        return $this->headers['size_download'] ?? null;
    }

    /**
     * Get the downlad speed
     *
     * @return ?float
     */
    public function getDownloadSpeed(): ?float
    {
        return $this->headers['speed_download'] ?? null;
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->error_message ?? curl_strerror($this->errer_number);
    }

    /**
     * Get error code
     *
     * @return int
     */
    public function getErrorNumber(): int
    {
        return $this->errer_number;
    }

    /**
     * Get the response content type
     *
     * @return ?string
     */
    public function getContentType(): ?string
    {
        return $this->headers['content_type'] ?? null;
    }
}
