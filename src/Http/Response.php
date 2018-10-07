<?php

namespace Bow\Http;

use Bow\Contracts\ResponseInterface;
use Bow\Exception\ResponseException;
use Bow\View\View;

class Response implements ResponseInterface
{
    /**
     * Liste de code http valide pour l'application
     * Sauf que l'utilisateur poura lui même rédéfinir
     * ces codes s'il utilise la fonction `header` de php
     */
    private static $header = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multipe Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
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
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    ];

    /**
     * @var Response
     */
    private static $instance;

    /**
     * @var string
     */
    private $message;

    /**
     * @var int
     */
    private $code;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var bool
     */
    private $download = false;

    /**
     * @var string
     */
    private $download_filename;

    /**
     * @var bool
     */
    private $override = false;

    /**
     * Response constructor.
     * @param string $message
     * @param int $code
     * @param array $headers
     */
    public function __construct($message = '', $code = 200, array $headers = [])
    {
        $this->message = $message;

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
    public function getMessage()
    {
        return $this->message;
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
     * @param string $message
     * @return Response
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get headers
     *
     * @param array $headers
     * @return Response
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Modifie les entêtes http
     *
     * @param  string $key
     * @param  string $value La nouvelle valeur a assigne à l'entête
     * @return self
     */
    public function addHeader($key, $value)
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * Télécharger le fichier donnée en argument
     *
     * @param string $file
     * @param null   $name
     * @param array  $headers
     * @param string $disposition
     * @return Response
     */
    public function download($file, $name = null, array $headers = array(), $disposition = 'attachment')
    {
        $type = mime_content_type($file);

        if ($name == null) {
            $name = basename($file);
        }

        $this->addHeader('Content-Disposition', $disposition.'; filename='.$name);

        $this->addHeader('Content-Type', $type);

        $this->addHeader('Content-Length', filesize($file));

        $this->addHeader('Content-Encoding', 'base64');

        foreach ($headers as $key => $value) {
            $this->addHeader($key, $value);
        }

        $this->download_filename = $file;

        $this->download = true;

        return $this;
    }

    /**
     * Modifie les entétes http
     *
     * @param  int $code
     * @return mixed
     */
    public function status($code)
    {
        if (in_array((int) $code, array_keys(self::$header), true)) {
            header('HTTP/1.1 '. $code .' '. self::$header[$code], $this->override, $code);
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
        header('HTTP/1.1 '. $this->code .' '. static::$header[$this->code], $this->override, $this->code);

        foreach ($this->getHeaders() as $key => $header) {
            header(sprintf('%s: %s', $key, $header));
        }

        if ($this->download) {
            readfile($this->download_filename);

            die;
        }

        return $this->message;
    }

    /**
     * Réponse de type JSON
     *
     * @param  mixed $data
     * @param  int   $code
     * @param  array $headers
     * @return bool
     */
    public function json($data, $code = 200, array $headers = [])
    {
        $this->addHeader('Content-Type', 'application/json; charset=UTF-8');

        foreach ($headers as $key => $value) {
            $this->addHeader($key, $value);
        }

        $this->message = json_encode($data);

        $this->status($code);

        return $this->send(json_encode($data), false);
    }

    /**
     * Equivalant à un echo, sauf qu'il termine l'application quand $stop = true
     *
     * @param  string|array|\stdClass $data
     * @param  int  $code
     * @param  array  $headers
     * @return mixed
     */
    public function send($data, $code = 200, array $headers = [])
    {
        if (is_array($data) || $data instanceof \stdClass || is_object($data)) {
            $data = json_encode($data);
        }

        $this->status($code);

        foreach ($headers as $key => $value) {
            $this->addHeader($key, $value);
        }

        return $this->buildHttpResponse();
    }

    /**
     * Permet de faire le rendu d'une vue.
     *
     * @param  $template
     * @param  array    $data
     * @return string
     * @throws
     */
    public function view($template, $data = [])
    {
        return View::parse($template, $data);
    }

    /**
     * @param $allow
     * @param $excepted
     * @return $this
     */
    private function accessControl($allow, $excepted)
    {
        if ($excepted === null) {
            $excepted = '*';
        }

        $this->addHeader($allow, $excepted);

        return $this;
    }

    /**
     * Active Access-control-Allow-Origin
     *
     * @param  array $excepted [optional]
     * @return Response
     * @throws
     */
    public function accessControlAllowOrigin(array $excepted)
    {
        if (!is_array($excepted)) {
            throw new \InvalidArgumentException(
                'Attend un tableau.' . gettype($excepted) . ' donner.',
                E_USER_ERROR
            );
        }

        return $this->accessControl(
            'Access-Control-Allow-Origin',
            implode(', ', $excepted)
        );
    }

    /**
     * Active Access-control-Allow-Methods
     *
     * @param  array $excepted [optional] $excepted
     * @return Response
     * @throws ResponseException
     */
    public function accessControlAllowMethods(array $excepted)
    {
        if (count($excepted) == 0) {
            throw new ResponseException('Le tableau est vide.' . gettype($excepted) . ' donner.', E_USER_ERROR);
        }

        return $this->accessControl('Access-Control-Allow-Methods', implode(', ', $excepted));
    }

    /**
     * Active Access-control-Allow-Headers
     *
     * @param  array $excepted [optional] $excepted
     * @return Response
     * @throws ResponseException
     */
    public function accessControlAllowHeaders(array $excepted)
    {
        if (count($excepted) == 0) {
            throw new ResponseException('Le tableau est vide.' . gettype($excepted) . ' donner.', E_USER_ERROR);
        }

        return $this->accessControl('Access-Control-Allow-Headers', implode(', ', $excepted));
    }

    /**
     * Active Access-control-Allow-Credentials
     *
     * @return Response
     */
    public function accessControlAllowCredentials()
    {
        return $this->accessControl('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * Active Access-control-Max-Age
     *
     * @param  string $excepted [optional] $excepted
     * @return Response
     * @throws ResponseException
     */
    public function accessControlMaxAge($excepted)
    {
        if (!is_numeric($excepted)) {
            throw new ResponseException(
                'La paramtere doit être un entier: ' . gettype($excepted) . ' donner.',
                E_USER_ERROR
            );
        }

        return $this->accessControl('Access-Control-Max-Age', $excepted);
    }

    /**
     * Active Access-control-Expose-Headers
     *
     * @param  array $excepted [optional] $excepted
     * @return Response
     * @throws ResponseException
     */
    public function accessControlExposeHeaders(array $excepted)
    {
        if (count($excepted) == 0) {
            throw new ResponseException(
                'Le tableau est vide.' . gettype($excepted) . ' donner.',
                E_USER_ERROR
            );
        }

        return $this->accessControl('Access-Control-Expose-Headers', implode(', ', $excepted));
    }

    /**
     * @inheritdoc
     */
    public function sendContent()
    {
        return $this->buildHttpResponse();
    }
}
