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
    private ?string $error = null;

    /**
     * The error number
     *
     * @var int
     */
    private int $errno;

    /**
     * Curl instance
     *
     * @var CurlHandle
     */
    private CurlHandle $ch;

    /**
     * The headers
     *
     * @var array
     */
    private array $headers = [];

    /**
     * Flag
     *
     * @var bool
     */
    private bool $executed = false;

    /**
     * Parser constructor.
     *
     * @param CurlHandle $ch
     */
    public function __construct(CurlHandle &$ch)
    {
        $this->ch = $ch;
    }

    /**
     * Get raw content
     *
     * @return mixed
     * @throws
     */
    public function raw(): string
    {
        if (!$this->returnTransfertToRaw()) {
            return null;
        }

        return $this->execute();
    }

    /**
     * Get response content
     *
     * @return mixed
     * @throws
     */
    public function getContent(): ?string
    {
        if (!$this->returnTransfertToPlain()) {
            return null;
        }

        return $this->execute();
    }

    /**
     * Get response content as json
     *
     * @param  array $default
     * @return bool|string
     * @throws
     */
    public function toJson(?array $default = null): bool|string
    {
        if (!$this->returnTransfertToPlain()) {
            if (is_array($default)) {
                return json_encode($default);
            }

            return false;
        }

        $data = $this->raw();

        return json_encode($data);
    }

    /**
     * Get response content as array
     *
     * @return mixed
     * @throws
     */
    public function toArray(): mixed
    {
        if (!$this->returnTransfert()) {
            $this->close();

            return ["error" => true, "message" => "Connat get information"];
        }

        return $this->execute();
    }

    /**
     * Set Curl CURLOPT_RETURNTRANSFER option
     *
     * @return bool
     */
    private function returnTransfert()
    {
        if (!curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true)) {
            $this->close();

            return false;
        }

        return true;
    }

    /**
     * Set Curl CURLOPT_BINARYTRANSFER option
     *
     * @return bool
     */
    private function returnTransfertToRaw()
    {
        if ($this->returnTransfert()) {
            if (!curl_setopt($this->ch, CURLOPT_HTTPGET, true)) {
                $this->close();

                return false;
            }
        }

        return true;
    }

    /**
     * Set Curl CURLOPT_TRANSFERTEXT option
     *
     * @return bool
     */
    private function returnTransfertToPlain()
    {
        if ($this->returnTransfert()) {
            if (!curl_setopt($this->ch, CURLOPT_TRANSFERTEXT, true)) {
                $this->close();

                return false;
            }
        }

        return true;
    }

    /**
     * Execute request
     *
     * @return string
     * @throws \Exception
     */
    private function execute(): string
    {
        $data = curl_exec($this->ch);

        $this->error = curl_error($this->ch);
        $this->errno = curl_errno($this->ch);
        $this->headers = curl_getinfo($this->ch);
        $this->executed = true;

        $this->close();

        if ($data === false) {
            throw new \Exception(curl_strerror($this->errno));
        }

        return $data;
    }

    /**
     * Get the response headers
     *
     * @return array
     * @throws
     */
    public function getHeaders(): array
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->headers;
    }

    /**
     * Get the response code
     *
     * @return ?int
     * @throws
     */
    public function getCode(): ?int
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->headers['http_code'] ?? null;
    }

    /**
     * Get the response executing time
     *
     * @return ?int
     * @throws
     */
    public function getExecutionTime(): ?int
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->headers['total_time'] ?? null;
    }

    /**
     * Get the request connexion time
     *
     * @return ?float
     * @throws
     */
    public function getConnexionTime(): ?float
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->headers['connect_time'] ?? null;
    }

    /**
     * Get the response upload size
     *
     * @return ?float
     * @throws
     */
    public function getUploadSize(): ?float
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->headers['size_upload'] ?? null;
    }

    /**
     * Get the request upload speed
     *
     * @return ?float
     * @throws
     */
    public function getUploadSpeed(): ?float
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->headers['speed_upload'] ?? null;
    }

    /**
     * Get the download size
     *
     * @return ?float
     * @throws
     */
    public function getDownloadSize(): ?float
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->headers['size_download'] ?? null;
    }

    /**
     * Get the downlad speed
     *
     * @return ?float
     * @throws
     */
    public function getDownloadSpeed(): ?float
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->headers['speed_download'] ?? null;
    }

    /**
     * Get error message
     *
     * @return string
     * @throws
     */
    public function getErrorMessage(): string
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->error;
    }

    /**
     * Get error code
     *
     * @return int
     * @throws
     */
    public function getErrorNumber(): int
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->errno;
    }

    /**
     * Get the response content type
     *
     * @return ?string
     * @throws
     */
    public function getContentType(): ?string
    {
        if (!$this->executed) {
            $this->execute();
        }

        return $this->headers['content_type'] ?? null;
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
}