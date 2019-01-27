<?php

namespace Bow\Http;

use Bow\Contracts\ResponseInterface;
use Bow\Exception\ResponseException;
use Bow\View\View;

class Response implements ResponseInterface
{
    /**
     * Valid http code list for the app Except that
     * the user can himself redefine these codes
     * if it uses the `header` function of php
     */
    private static $header = [
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
    private static $instance;

    /**
     * The Response content
     *
     * @var string
     */
    private $content;

    /**
     * The Response code
     *
     * @var int
     */
    private $code;

    /**
     * The added headers
     *
     * @var array
     */
    private $headers = [];

    /**
     * Downloadable flag
     *
     * @var bool
     */
    private $download = false;

    /**
     * The downloadable filenme
     *
     * @var string
     */
    private $download_filename;

    /**
     * The override the respons
     *
     * @var bool
     */
    private $override = false;

    /**
     * Response constructor.
     *
     * @param string $content
     * @param int $code
     * @param array $headers
     */
    private function __construct($content = '', $code = 200, array $headers = [])
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
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Get response message
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get status code
     *
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get response message
     *
     * @param string $content
     * @return Response
     */
    public function setContent($content)
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
    public function withHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Modify http headers
     *
     * @param  string $key
     * @param  string $value
     * @return self
     */
    public function addHeader($key, $value)
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * Download the given file as an argument
     *
     * @param string $file
     * @param null   $filename
     * @param array  $headers
     * @param string $disposition
     * @return string
     */
    public function download($file, $filename = null, array $headers = [], $disposition = 'attachment')
    {
        $type = mime_content_type($file);

        if ($filename == null) {
            $filename = basename($file);
        }

        $this->addHeader('Content-Disposition', $disposition.'; filename='.$filename);

        $this->addHeader('Content-Type', $type);

        $this->addHeader('Content-Length', filesize($file));

        $this->addHeader('Content-Encoding', 'base64');

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
    public function status($code)
    {
        if (in_array((int) $code, array_keys(self::$header), true)) {
            $this->code = $code;

            @header('HTTP/1.1 '. $code .' '. self::$header[$code], $this->override, $code);
        }

        return $this;
    }

    /**
     * Build HTTP Response
     *
     * @return string
     */
    private function buildHttpResponse()
    {
        $status_text = static::$header[$this->code] ?? 'Unkdown';

        @header('HTTP/1.1 '. $this->code .' '. $status_text, $this->override, $this->code);

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
    public function json($data, $code = 200, array $headers = [])
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
     * @param  string|array|\stdClass $data
     * @param  int  $code
     * @param  array  $headers
     * @return string
     */
    public function send($data, $code = 200, array $headers = [])
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
     * @param  $template
     * @param  array $data
     * @param  int $code
     * @param  array $headers
     * @return string
     * @throws
     */
    public function render($template, $data = [], $code = 200, array $headers = [])
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
     * @return AccessControl
     */
    public function accessControl()
    {
        return new AccessControl($this);
    }

    /**
     * @inheritdoc
     */
    public function sendContent()
    {
        echo $this->buildHttpResponse();

        return;
    }
}
