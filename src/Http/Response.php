<?php
namespace Bow\Http;

use Jade\Jade;
use ErrorException;
use Bow\Support\Str;
use Bow\Support\Security;
use Bow\Exception\ViewException;
use Bow\Exception\ResponseException;
use Bow\Application\AppConfiguration;

/**
 * Class Response
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Http
 */
class Response
{
    /**
     * Singleton
     * @var self
     */
    private static $instance = null;

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
        501	=> 'Not Implemented',
        502	=> 'Bad Gateway',
        503 => 'Service Unavailable',
        504	=> 'Gateway Timeout',
        505	=> 'HTTP Version Not Supported',
    ];

    /**
     * Instance de l'application
     * @var AppConfiguration
     */
    private $config;

    /**
     * Constructeur de l'application
     *
     * @param AppConfiguration $appConfig
     */
    private function __construct(AppConfiguration $appConfig)
    {
        $this->config = $appConfig;
    }

    /**
     * Singleton loader
     *
     * @param AppConfiguration $appConfig
     * @return self
     */
    public static function configure(AppConfiguration $appConfig)
    {
        if (self::$instance === null) {
            self::$instance = new self($appConfig);
        }

        return self::$instance;
    }

    /**
     * Retourne l'instance de Response
     *
     * @return Response
     */
    public static function takeInstance()
    {
        return self::$instance;
    }

    /**
     * Modifie les entêtes http
     *
     * @param string $key 	Le nom de l'entête
     * @param string $value La nouvelle valeur a assigne à l'entête
     * @return self
     */
    public function addHeader($key, $value)
    {
        header($key.': '.$value);
        return $this;
    }

    /**
     * redirect, permet de lancer une redirection vers l'url passé en paramêtre
     *
     * @param string|array $path L'url de rédirection
     * Si $path est un tableau :
     * 	$url = [
     * 		'url' => '//'
     * 		'?' => [
     * 			'name' => 'dakia',
     * 			'lastname' => 'franck',
     * 			'id' => '1',
     * 		],
     * 		'$' => 'hello'
     * ];
     *
     */
    public function redirect($path)
    {
        if (is_string($path)) {
            $path = ['url' => $path];
        }

        $url = $path['url'];

        if (isset($path['?'])) {
            $url .= '?';
            $i = 0;
            foreach($path['?'] as $key => $value) {
                if ($i > 0) {
                    $url .= '&';
                }
                $url .= $key . '=' . $value;
                $i++;
            }
        }

        if (isset($path['#'])) {
            $url .= '#' . $path['#'];
        }

        header('Location: ' . $url);
        die;
    }

    /**
     * redirectTo404, rédirige vers 404
     * @return self
     */
    public function redirectTo404()
    {
        $this->code(404);
        return $this;
    }

    /**
     * Télécharger le fichier donnée en argument
     *
     * @param string $file
     * @param null $name
     * @param array $headers
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

        foreach($headers as $key => $value) {
            $this->addHeader($key, $value);
        }

        readfile($file);
    }

    /**
     * Modifie les entétes http
     *
     * @param int  $code 	 Le code de la réponse HTTP
     * @param bool $override Permet de remplacer l'entête ecrite précédement quand la valeur est a 'true'
     * @return bool|void
     */
    public function code($code, $override = false)
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
     * @param mixed $data Les données à transformer en JSON
     * @param int 	$code Le code de la réponse HTTP
     * @param bool 	$end  Quand est à true il termine le processus
     */
    public function json($data, $code = 200, $end = false)
    {
        if (is_bool($code)) {
            $end = $code;
            $code = 200;
        }

        $this->forceInUTF8();
        $this->addHeader('Content-Type', 'application/json; charset=UTF-8');
        $this->code($code);
        $this->send(json_encode($data), $end);
    }

    /**
     * Permet de forcer l'encodage en utf-8
     */
    public function forceInUTF8()
    {
        mb_internal_encoding('UTF-8');
        mb_http_output('UTF-8');
    }

    /**
     * sendFile, require $filename
     *
     * @param string $filename
     * @param array $bind
     * @throws ViewException
     * @return mixed
     */
    public function sendFile($filename, $bind = [])
    {
        $filename = preg_replace('/@|#|\./', '/', $filename);

        if ($this->config->getViewpath() !== null) {
            $tmp = $this->config->getViewpath() .'/'. $filename . '.php';
            if (!file_exists($tmp)) {
                $filename = $this->config->getViewpath() .'/'. $filename . '.html';
            } else {
                $filename = $tmp;
            }
        }

        if (!file_exists($filename)) {
            throw new ViewException('La vue '.$filename.' n\'exist pas.', E_ERROR);
        }

        @extract($bind);
        // Rendu du fichier demandé.

        return require $filename;
    }

    /**
     * render, lance le rendu utilisant le template définir <<mustache|twig|jade>>
     *
     * @param string  $filename Le nom de la vue
     * @param array   $bind     Les données à passer la vue
     * @param integer|null $code [optional] Le code http
     * @throws ViewException|ResponseException
     *
     * @return bool
     */
    public function view($filename, $bind = [], $code = 200)
    {
        if (is_int($bind)) {
            $code = $bind;
            $bind = [];
        }

        $filename = preg_replace('/@|\./', '/', $filename) . $this->config->getTemplateExtension();

        // Vérification de l'existance du fichier
        if ($this->config->getViewpath() !== null) {
            if (!is_file($this->config->getViewpath() . '/' . $filename)) {
                throw new ViewException('La vue ['.$filename.'] n\'exist pas. ' . $this->config->getViewpath() . '/' . $filename, E_ERROR);
            }
        } else {
            if (!is_file($filename)) {
                throw new ViewException('La vue ['.$filename.'] n\'exist pas!.', E_ERROR);
            }
        }

        // Modification du code http
        if ($code !== null) {
            $this->code($code);
        }

        if ($this->config->getTemplateEngine() == 'php') {
            if ($this->config->getViewpath() !== null) {
                $filename = $this->config->getViewpath() . '/' . $filename;
            }
            ob_start();
            require $filename;
            return $this->send(ob_get_clean());
        }

        // Chargement du template.
        $template = $this->templateLoader();

        if ($this->config->getTemplateEngine() == 'twig') {
            return $this->send($template->render($filename, $bind));
        }

        if (in_array($this->config->getTemplateEngine(), ['mustache', 'jade'])) {
            return $this->send($template->render(file_get_contents($filename), $bind));
        }

        throw new ResponseException('Le moteur de template n\'est pas défini.', E_USER_ERROR);
    }

    /**
     * templateLoader, charge le moteur template à utiliser.
     *
     * @throws ErrorException
     * @return \Twig_Environment|\Mustache_Engine|\Jade\Jade
     */
    private function templateLoader()
    {
        if ($this->config->getTemplateEngine() !== null) {
            if (!in_array($this->config->getTemplateEngine(), ['twig', 'mustache', 'jade'], true)) {
                throw new ErrorException('Le moteur de template n\'est pas implementé.', E_USER_ERROR);
            }
        } else {
            throw new ResponseException('Le moteur de template non défini.', E_USER_ERROR);
        }

        $tpl = null;

        if ($this->config->getTemplateEngine() == 'twig') {
            $loader = new \Twig_Loader_Filesystem($this->config->getViewpath());
            $tpl = new \Twig_Environment($loader, [
                'cache' => $this->config->getCachepath(),
                'auto_reload' => $this->config->getCacheAutoReload(),
                'debug' => $this->config->getLoggerMode() == 'develepment' ? true : false
            ]);
            /**
             * - Ajout de variable globale
             * dans le cadre de l'utilisation de Twig
             */
            $tpl->addGlobal('public', $this->config->getPublicPath());
            $tpl->addGlobal('root', $this->config->getApproot());

            /**
             * - Ajout de fonction global
             *  dans le cadre de l'utilisation de Twig
             */
            $tpl->addFunction(new \Twig_SimpleFunction('secure', function($data) {
                return Security::sanitaze($data, true);
            }));
            $tpl->addFunction(new \Twig_SimpleFunction('sanitaze', function($data) {
                return Security::sanitaze($data);
            }));
            $tpl->addFunction(new \Twig_SimpleFunction('csrf_field', function() {
                return Security::getCsrfToken()->field;
            }));
            $tpl->addFunction(new \Twig_SimpleFunction('csrf_token', function() {
                return Security::getCsrfToken()->token;
            }));

            $tpl->addFunction(new \Twig_SimpleFunction('slugify', [Str::class, 'slugify']));
            return $tpl;
        }

        if ($this->config->getTemplateEngine() == 'mustache') {
            return new \Mustache_Engine([
                'cache' => $this->config->getCachepath(),
                'loader' => new \Mustache_Loader_FilesystemLoader($this->config->getViewpath()),
                'helpers' => [
                    'secure' => function($data) {
                        return Security::sanitaze($data, true);
                    },
                    'sanitaze' => function($data) {
                        return Security::sanitaze($data);
                    },
                    'slugify' => function($data) {
                        return Str::slugify($data);
                    },
                    'csrf_token' => function() {
                        return Security::getCsrfToken()->token;
                    },
                    'csrf_field' => function() {
                        return Security::getCsrfToken()->field;
                    },
                    'public', $this->config->getPublicPath(),
                    'root', $this->config->getApproot()
                ]
            ]);
        }

        return new Jade([
            'cache' => $this->config->getCachepath(),
            'prettyprint' => true,
            'extension' => $this->config->getTemplateExtension()
        ]);
    }

    /**
     * Equivalant à un echo, sauf qu'il termine l'application quand $stop = true
     *
     * @param string|array|\StdClass $data
     * @param bool|false $stop
     * @return mixed
     */
    public function send($data, $stop = false)
    {
        if (is_array($data) || ($data instanceof \stdClass)) {
            $data = json_encode($data);
        }

        echo $data;

        if ($stop) {
            die();
        }

        return true;
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
     * @param array $excepted [optional]
     * @return Response
     * @throws ResponseException
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
     * @param array $excepted [optional] $excepted
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
     * @param array $excepted [optional] $excepted
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
     * @param string $excepted [optional] $excepted
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
     * @param array $excepted [optional] $excepted
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