<?php
namespace Bow\Http;

use Bow\View\View;
use Bow\Exception\ResponseException;

class Response
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
     * @return Response
     */
    public static function singleton()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Modifie les entêtes http
     *
     * @param  string $key   Le nom de
     *                       l'entête
     * @param  string $value La nouvelle valeur a assigne à l'entête
     * @return self
     */
    public function addHeader($key, $value)
    {
        header($key.': '.$value);

        return $this;
    }

    /**
     * Télécharger le fichier donnée en argument
     *
     * @param string $file
     * @param null   $name
     * @param array  $headers
     * @param string $disposition
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

        readfile($file);

        die;
    }

    /**
     * Modifie les entétes http
     *
     * @param  int  $code     Le code de la réponse
     *                        HTTP
     * @param  bool $override Permet de remplacer l'entête ecrite précédement quand la valeur est a 'true'
     * @return mixed
     */
    public function statusCode($code, $override = false)
    {
        $r = true;

        if (in_array((int) $code, array_keys(self::$header), true)) {
            header('HTTP/1.1 '. $code .' '. self::$header[$code], $override, $code);
        } else {
            $r = false;
        }

        return $r;
    }

    /**
     * Réponse de type JSON
     *
     * @param  mixed $data    Les données à transformer en
     *                        JSON
     * @param  int   $code    Le code de la
     *                        réponse HTTP
     * @param  array $headers
     * @return bool
     */
    public function json($data, $code = 200, array $headers = [])
    {
        $this->addHeader('Content-Type', 'application/json; charset=UTF-8');

        foreach ($headers as $key => $value) {
            $this->addHeader($key, $value);
        }

        $this->statusCode($code);

        return $this->send(json_encode($data), false);
    }

    /**
     * Equivalant à un echo, sauf qu'il termine l'application quand $stop = true
     *
     * @param  string|array|\StdClass $data
     * @param  bool|false             $stop
     * @return mixed
     */
    public function send($data, $stop = false)
    {
        if (is_array($data) || ($data instanceof \stdClass)) {
            $data = json_encode($data);
        }

        echo $data;

        if (!$stop) {
            return true;
        }

        die();
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
        return View::make($template, $data);
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
            throw new \InvalidArgumentException('Attend un tableau.' . gettype($excepted) . ' donner.', E_USER_ERROR);
        }

        return $this->accessControl('Access-Control-Allow-Origin', implode(', ', $excepted));
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
            throw new ResponseException('La paramtere doit être un entier: ' . gettype($excepted) . ' donner.', E_USER_ERROR);
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
            throw new ResponseException('Le tableau est vide.' . gettype($excepted) . ' donner.', E_USER_ERROR);
        }

        return $this->accessControl('Access-Control-Expose-Headers', implode(', ', $excepted));
    }
}
