<?php

declare(strict_types=1);

namespace Bow\Http;

use Bow\Contracts\ResponseInterface;
use Bow\View\View;

class Response implements ResponseInterface
{
    /**
     * Valid http code list for the app Except that
     * the user can himself redefine these codes
     * if it uses the `header` function of php
     */
    private static array $status_codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multipe Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication',
        408 => 'Request Time Out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupport Media',
        416 => 'Range Not Statisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        499 => 'Client Closed Request',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    ];

    /**
     * The Response instamce
     *
     * @var Response
     */
    private static ?Response $instance = null;

    /**
     * The Response content
     *
     * @var string
     */
    private ?string $content = '';

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
     * The downloadable filenme
     *
     * @var string
     */
    private ?string $download_filename = null;

    /**
     * The override the respons
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
     * Get response message
     *
     * @return ?string
     */
    public function getContent(): ?string
    {
        return (string) $this->content;
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
     * @param string $content
     * @return Response
     */
    public function setContent($content): Response
    {
        $this->content = $content;

        return $this;
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
     * Modify http headers
     *
     * @param  string $key
     * @param  string $value
     * @return Response
     */
    public function addHeader(string $key, string $value): Response
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * Download the given file as an argument
     *
     * @param string $file
     * @param null   $filename
     * @param string $disposition
     * @param array  $headers
     * @return string
     */
    public function download(
        string $file,
        ?string $filename = null,
        string $disposition = 'attachment',
        array $headers = []
    ): string {
        $type = mime_content_type($file);

        if (is_null($filename)) {
            $filename = basename($file);
        }

        $this->addHeader('Content-Disposition', $disposition . '; filename=' . $filename);
        $this->addHeader('Content-Type', $type);

        $file_size = filesize($file);
        $this->addHeader('Content-Length', (string) (is_int($file_size) ? $file_size : ''));
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
     * Modify http headers
     *
     * @param  int $code
     * @return mixed
     */
    public function status(int $code): Response
    {
        if (in_array($code, array_keys(static::$status_codes), true)) {
            $this->code = $code;
            @header('HTTP/1.1 ' . $code . ' ' . static::$status_codes[$code], $this->override, $code);
        }

        return $this;
    }

    /**
     * Build HTTP Response
     *
     * @return string
     */
    private function buildHttpResponse(): string
    {
        $status_text = static::$status_codes[$this->code] ?? 'Unkdown';
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
     * JSON response
     *
     * @param  mixed $data
     * @param  int   $code
     * @param  array $headers
     * @return string
     */
    public function json($data, $code = 200, array $headers = []): string
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
     * Equivalent to an echo, except that it ends the application
     *
     * @param  mixed $data
     * @param  int  $code
     * @param  array  $headers
     * @return string
     */
    public function send(mixed $data, int $code = 200, array $headers = []): string
    {
        if (is_array($data) || $data instanceof \stdClass || is_object($data)) {
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
     * Make view rendering
     *
     * @param  string $template
     * @param  array $data
     * @param  int $code
     * @param  array $headers
     * @return string
     * @throws
     */
    public function render(string $template, array $data = [], int $code = 200, array $headers = []): string
    {
        $this->code = $code;

        $this->withHeaders($headers);

        $view = View::parse($template, $data);

        $this->content = $view->sendContent();

        return $this->buildHttpResponse();
    }

    /**
     * Get accessControl instance
     *
     * @return ServerAccessControl
     */
    public function serverAccessControl(): ServerAccessControl
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
