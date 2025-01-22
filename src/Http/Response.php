<?php

declare(strict_types=1);

namespace Bow\Http;

use Bow\Contracts\ResponseInterface;
use Bow\View\View;
use stdClass;

class Response implements ResponseInterface
{
    /**
     * The Response instance
     *
     * @var ?Response
     */
    private static ?Response $instance = null;

    /**
     * The Response content
     *
     * @var string
     */
    private string $content = '';

    /**
     * The Response code
     *
     * @var int
     */
    private int $code;

    /**
     * The added headers
     *
     * @var array
     */
    private array $headers = [];

    /**
     * Downloadable flag
     *
     * @var bool
     */
    private bool $download = false;

    /**
     * The downloadable filename
     *
     * @var ?string
     */
    private ?string $download_filename = null;

    /**
     * The override the response
     *
     * @var bool
     */
    private bool $override = false;

    /**
     * Response constructor.
     *
     * @param string $content
     * @param int $code
     * @param array $headers
     */
    private function __construct(string $content = '', int $code = 200, array $headers = [])
    {
        $this->content = $content;
        $this->code = $code;
        $this->headers = $headers;
        $this->override = false;
    }

    /**
     * Get response
     *
     * @return Response
     */
    public static function getInstance(): Response
    {
        if (is_null(static::$instance)) {
            static::$instance = new Response();
        }

        return static::$instance;
    }

    /**
     * Get status code
     *
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Add http headers
     *
     * @param array $headers
     * @return Response
     */
    public function addHeaders(array $headers): Response
    {
        $this->headers = [...$this->headers, ...$headers];

        return $this;
    }

    /**
     * Download the given file as an argument
     *
     * @param string $file
     * @param ?string $filename
     * @param array $headers
     * @return string
     */
    public function download(
        string  $file,
        ?string $filename = null,
        array   $headers = []
    ): string
    {
        $type = mime_content_type($file);

        if (is_null($filename)) {
            $filename = basename($file);
        }

        $disposition = $headers["disposition"] ?? 'attachment';

        $this->addHeader('Content-Disposition', $disposition . '; filename=' . $filename);
        $this->addHeader('Content-Type', $type);

        $file_size = filesize($file);
        $this->addHeader('Content-Length', (string)(is_int($file_size) ? $file_size : ''));
        $this->addHeader('Content-Encoding', 'base64');

        // We put the new headers
        foreach ($headers as $key => $value) {
            $this->addHeader($key, $value);
        }

        $this->download_filename = $file;
        $this->download = true;

        return $this->buildHttpResponse();
    }

    /**
     * Add http header
     *
     * @param string $key
     * @param string $value
     * @return Response
     */
    public function addHeader(string $key, string $value): Response
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * Build HTTP Response
     *
     * @return string
     */
    private function buildHttpResponse(): string
    {
        $status_text = HttpStatus::getMessage($this->code) ?? 'Unknown';

        @header('HTTP/1.1 ' . $this->code . ' ' . $status_text, $this->override, $this->code);

        foreach ($this->getHeaders() as $key => $header) {
            header(sprintf('%s: %s', $key, $header));
        }

        if ($this->download) {
            readfile($this->download_filename);
            die;
        }

        return $this->getContent();
    }

    /**
     * Get headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get response message
     *
     * @return ?string
     */
    public function getContent(): ?string
    {
        return (string)$this->content;
    }

    /**
     * Get response message
     *
     * @param string $content
     * @return Response
     */
    public function setContent(string $content): Response
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Modify http headers
     *
     * @param int $code
     * @return mixed
     */
    public function status(int $code): Response
    {
        $this->code = $code;

        if (in_array($code, HttpStatus::getCodes(), true)) {
            @header('HTTP/1.1 ' . $code . ' ' . HttpStatus::getMessage($code), $this->override, $code);
        }

        return $this;
    }

    /**
     * Equivalent to an echo, except that it ends the application
     *
     * @param mixed $data
     * @param int $code
     * @param array $headers
     * @return string
     */
    public function send(mixed $data, int $code = 200, array $headers = []): string
    {
        if (is_array($data) || $data instanceof stdClass || is_object($data)) {
            return $this->json($data, $code, $headers);
        }

        $this->code = $code;

        foreach ($headers as $key => $value) {
            $this->addHeader($key, $value);
        }

        $this->content = $data;

        return $this->buildHttpResponse();
    }

    /**
     * JSON response
     *
     * @param mixed $data
     * @param int $code
     * @param array $headers
     * @return string
     */
    public function json(mixed $data, int $code = 200, array $headers = []): string
    {
        $this->addHeader('Content-Type', 'application/json; charset=UTF-8');

        foreach ($headers as $key => $value) {
            $this->addHeader($key, $value);
        }

        $this->content = json_encode($data);
        $this->code = $code;

        return $this->buildHttpResponse();
    }

    /**
     * Make view rendering
     *
     * @param string $template
     * @param array $data
     * @param int $code
     * @param array $headers
     * @return string
     * @throws
     */
    public function render(string $template, array $data = [], int $code = 200, array $headers = []): string
    {
        $this->code = $code;

        $this->withHeaders($headers);

        $view = View::parse($template, $data);

        $this->content = $view->getContent();

        return $this->buildHttpResponse();
    }

    /**
     * Get headers
     *
     * @param array $headers
     * @return Response
     */
    public function withHeaders(array $headers): Response
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Modify the service access control from ServerAccessControl instance
     *
     * @return ServerAccessControl
     */
    public function getServerAccessControl(): ServerAccessControl
    {
        return new ServerAccessControl($this);
    }

    /**
     * @inheritdoc
     */
    public function sendContent(): void
    {
        echo $this->buildHttpResponse();

        return;
    }
}
