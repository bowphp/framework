<?php
namespace Bow\Application;

use Bow\Event\Event;
use Bow\Http\Request;
use Bow\Http\Response;
use Bow\Support\DateAccess;
use Bow\Http\Exception\HttpException;
use Bow\Firewall\ApplicationCsrfFirewall;
use Bow\Application\Exception\RouterException;
use Bow\Application\Exception\ApplicationException;

/**
 * Create and maintener by diagnostic developpers teams:
 *
 * @author Etchien Boa <geekroot9@gmail.com>
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Core
 */
class Application
{
    /**
     * @var string
     */
    private $version = '0.2.1';

    /**
     * @var array
     */
    private $error_code = [];

    /**
     * @var array
     */
    private $globale_firewall = [];

    /**
     * Définition de contrainte sur un route.
     *
     * @var array
     */
    private $with = [];

    /**
     * Branchement global sur un liste de route
     *
     * @var string
     */
    private $branch;

    /**
     * @var string
     */
    private $special_method;

    /**
     * Method Http courrante.
     *
     * @var array
     */
    private $current;

    /**
     * Patter Singleton
     *
     * @var Application
     */
    private static $instance;

    /**
     * Collecteur de route.
     *
     * @var array
     */
    private $routes = [];

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Configuration|null
     */
    private $config;

    /**
     * @var array
     */
    private $local = [];

    /**
     * @var bool
     */
    private $disable_x_powered_by = false;

    /**
     * Private construction
     *
     * @param Configuration $config
     * @param Request $request
     * @param Response $response
     */
    private function __construct(Configuration $config, Request $request, Response $response)
    {
        $this->config = $config;
        $this->request = $request;
        $this->response = $response;

        /**
         * Application de la timezone
         */
        DateAccess::setTimezone($this->config['app.timezone']);

        /**
         * Chargement des services
         */
        $services = $this->config['app.classes'];

        if (!isset($services['services'])) {
            return;
        }

        foreach ($services['services'] as $service) {
            if (!class_exists($service)) {
                continue;
            }

            $service = new $service($this);
            $service_called_name = call_user_func([$service, 'getName']);
            /**
             * Configuration du service
             */
            call_user_func_array([$service, 'make'], [$config]);
            if (Event::bound($service_called_name.'.services.stared')) {
                Event::emit($service_called_name.'.services.stared');
            }
            /**
             * Démarrage du service.
             */
            call_user_func_array([$service, 'start'], []);
            if (Event::bound($service_called_name.'.services.maked')) {
                Event::emit($service_called_name.'.services.maked');
            }
        }
    }

    /**
     * Private __clone
     */
    private function __clone(){}

    /**
     * Pattern Singleton.
     *
     * @param Configuration $config
     * @param Request $request
     * @param Response $response
     * @return Application
     */
    public static function make(Configuration $config, Request $request, Response $response)
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($config, $request, $response);
        }

        return static::$instance;
    }

    /**
     * mount, ajoute un branchement.
     *
     * @param string $branch
     * @param callable $cb
     * @throws ApplicationException
     * @return Application
     */
    public function group($branch, callable $cb)
    {
        $branch = rtrim($branch, '/');

        if (!preg_match('@^/@', $branch)) {
            $branch = '/' . $branch;
        }

        if ($this->branch !== null) {
            $this->branch .= $branch;
        } else {
            $this->branch = $branch;
        }

        call_user_func_array($cb, [$this]);

        $this->branch = '';
        $this->globale_firewall = [];

        return $this;
    }

    /**
     * Permet d'associer un firewall sur une url
     *
     * @param array $firewall
     * @param callable $cb
     * @return Application
     */
    public function firewall($firewall = [], callable $cb)
    {
        $firewall = is_array($firewall) ? $firewall : [$firewall];
        $this->globale_firewall = $firewall;
        $cb($this);
        $this->globale_firewall = [];
        return $this;
    }

    /**
     * get, route de type GET ou bien retourne les variable ajoutés dans Bow
     *
     * @param string $path La route à mapper
     * @param callable|array $cb  La fonction à lancer
     *
     * @return Application|string
     */
    public function get($path, $cb)
    {
        return $this->routeLoader('GET', $path, $cb);
    }

    /**
     * post, route de type POST
     *
     * @param string $path La route à mapper
     * @param callable $cb La fonction à lancer
     *
     * @return Application
     */
    public function post($path, $cb)
    {
        $input = $this->request->input();

        if (!$input->has('_method')) {
            return $this->routeLoader('POST', $path, $cb);
        }

        $method = strtoupper($input->get('_method'));

        if (in_array($method, ['DELETE', 'PUT'])) {
            $this->special_method = $method;
            $this->addHttpVerbe($method, $path, $cb);
        }

        return $this;
    }

    /**
     * any, route de tout type GET|POST|DELETE|PUT|OPTIONS|PATCH
     *
     * @param string $path La route à mapper
     * @param Callable $cb La fonction à lancer
     *
     * @return Application
     */
    public function any($path, Callable $cb)
    {
        foreach(['options', 'patch', 'post', 'delete', 'put', 'get'] as $method) {
            $this->$method($path, $cb);
        }

        return $this;
    }

    /**
     * delete, route de tout type DELETE
     *
     * @param string $path La route à mapper
     * @param callable $cb La fonction à lancer
     *
     * @return Application
     */
    public function delete($path, $cb)
    {
        return $this->addHttpVerbe('DELETE', $path, $cb);
    }

    /**
     * put, route de tout type PUT
     *
     * @param string $path La route à mapper
     * @param callable $cb La fonction à lancer
     *
     * @return Application
     */
    public function put($path, $cb)
    {
        return $this->addHttpVerbe('PUT', $path, $cb);
    }

    /**
     * patch, route de tout type PATCH
     *
     * @param string $path La route à mapper
     * @param callable $cb La fonction à lancer
     *
     * @return Application
     */
    public function patch($path, $cb)
    {
        return $this->addHttpVerbe('PATCH', $path, $cb);
    }

    /**
     * patch, route de tout type PATCH
     *
     * @param string 				$path   La route à mapper
     * @param callable 				$cb     La fonction à lancer
     * @return Application
     */
    public function options($path, Callable $cb)
    {
        return $this->addHttpVerbe('OPTIONS', $path, $cb);
    }

    /**
     * code, Lance une fonction en fonction du code d'erreur HTTP
     *
     * @param int $code Le code d'erreur
     * @param callable $cb La fonction à lancer
     * @return Application
     */
    public function code($code, callable $cb)
    {
        $this->error_code[$code] = $cb;
        return $this;
    }

    /**
     * match, route de tout type de method
     *
     * @param array $methods
     * @param string $path
     * @param callable $cb La fonction à lancer
     * @return Application
     */
    public function match(array $methods, $path, Callable $cb = null)
    {
        foreach($methods as $method) {
            if ($this->request->method() === strtoupper($method)) {
                $this->routeLoader(strtoupper($method), $path , $cb);
            }
        }

        return $this;
    }

    /**
     * addHttpVerbe, permet d'ajouter les autres verbes http
     * [PUT, DELETE, UPDATE, HEAD, PATCH]
     *
     * @param string $method La methode HTTP
     * @param string $path La route à mapper
     * @param callable|array|string $cb La fonction à lancer
     *
     * @return Application
     */
    private function addHttpVerbe($method, $path, $cb)
    {
        $input = $this->request->input();

        if ($input->has('_method')) {
            if ($input->get('_method') === $method) {
                $method = $input->get('_method');
            }
        }

        return $this->routeLoader($method, $path, $cb);
    }

    /**
     * routeLoader, lance le chargement d'une route.
     *
     * @param string $method La methode HTTP
     * @param string $path La route à mapper
     * @param Callable|string|array $cb La fonction à lancer
     *
     * @return Application
     */
    private function routeLoader($method, $path, $cb)
    {
        if (!preg_match('@^/@', $path)) {
            $path = '/' . $path;
        }

        // construction du path original en fonction de la configuration de l'application
        $path = $this->config['app.root'] . $this->branch . $path;

        // route courante
        // methode courante
        $this->current['path'] = $path;
        $this->current['method'] = $method;

        // Ajout d'un nouvelle route sur l'en definie.
        switch (true) {
            case !is_array($cb) && !empty($this->globale_firewall):
                $cb = [
                    'firewall' => $this->globale_firewall,
                    'uses' => $cb
                ];
                break;
            case !is_callable($cb) && isset($cb['firewall']) && !empty($this->globale_firewall):
                if (!is_array($cb['firewall'])) {
                    $cb['firewall'] = [$cb['firewall']];
                }
                $cb['firewall'] = array_merge(
                    $this->globale_firewall, $cb['firewall']
                );
                break;
        }

        // Ajout de nouvelle route
        $this->routes[$method][] = new Route($path, $cb);

        return $this;
    }

    /**
     * Lance une personnalisation de route.
     *
     * @param array|string $var
     * @param string $regexContrainte
     *
     * @return Application
     */
    public function where($var, $regexContrainte = null)
    {
        if (is_array($var)) {
            $otherRule = $var;
        } else {
            $otherRule = [$var => $regexContrainte];
        }

        // Quand le tableau de collection des contraintes sur les variables est vide
        if (empty($this->with)) {
            // Si on crée un nouvelle entre dans le tableau avec le nom de la methode HTTP
            // courante dont la valeur est un tableau, ensuite dans ce tableau on crée une
            // autre entré avec comme clé le path définie par le developpeur et pour valeur
            // les contraintes sur les variables.
            $this->with[$this->current['method']] = [];
            $this->with[$this->current['method']][$this->current['path']] = $otherRule;
        } else {
            // Quand le tableau de collection des contraintes sur les variables n'est pas vide
            // On vérifie l'existance de clé portant le nom de la methode HTTP courant
            // si la elle existe alors on fusionne l'ancien contenu avec la nouvelle.
            if (array_key_exists($this->current['method'], $this->with)) {
                $this->with[$this->current['method']] = array_merge(
                    $this->with[$this->current['method']],
                    [$this->current['path'] => $otherRule]
                );
            }
        }

        return $this;
    }

    /**
     * Lanceur de l'application
     *
     * @return mixed
     * @throws RouterException
     */
    public function run()
    {
        if (php_sapi_name() == 'cli') {
            return true;
        }

        if (env('MODE') == 'down') {
            abort(503);
            return true;
        }

        // Ajout de l'entête X-Powered-By
        if (!$this->disable_x_powered_by) {
            $this->response->addHeader('X-Powered-By', 'Bow Framework');
        }

        $this->branch = '';
        $method = $this->request->method();

        // vérification de l'existance d'une methode spécial
        // de type DELETE, PUT
        if ($method == 'POST') {
            if ($this->special_method !== null) {
                $method = $this->special_method;
            }
        }

        if ($method == 'PUT' || $method == 'POST') {
            $this->executeNativeFirewall();
        }

        // drapeaux d'erreur.
        $error = false;

        // Vérification de l'existance de methode de la requete dans
        // la collection de route
        if (!isset($this->routes[$method])) {
            // Vérification et appel de la fonction du branchement 404
            $this->response->statusCode(404);

            if (empty($this->error_code)) {
                $this->response->send('Cannot ' . $method . ' ' . $this->request->uri() . ' 404');
            }

            return false;
        }

        foreach ($this->routes[$method] as $key => $route) {
            // route doit être une instance de Route
            if (!($route instanceof Route)) {
                continue;
            }

            // Récupération du contenu des critères défini
            if (isset($this->with[$method][$route->getPath()])) {
                $with = $this->with[$method][$route->getPath()];
            } else {
                $with = [];
            }

            // Lancement de la recherche de la methode qui arrivée dans la requête
            // ensuite lancement de la vérification de l'url de la requête
            if (!$route->match($this->request->uri(), $with)) {
                $error = true;
                continue;
            }

            $this->current['path'] = $route->getPath();

            // Appel de l'action associer à la route
            $response = $route->call($this->request, $this->config['app']['classes']);

            if (is_string($response)) {
                $this->response->send($response);
            } else if (is_array($response) || is_object($response)) {
                $this->response->json($response);
            }

            $error = false;
            break;
        }

        // Gestion de erreur
        if (!$error) {
            return true;
        }

        $this->response->statusCode(404);

        if (in_array(404, array_keys($this->error_code))) {
            $this->response->statusCode(404);
            $r = call_user_func($this->error_code[404]);
            return $this->response->send($r, true);
        }

        if ($this->config['view.404'] != false) {
            return $this->response->send(
                $this->response->view($this->config['view.404'])
            );
        }

        throw new RouterException('La route "'.$this->request->uri().'" n\'existe pas', E_ERROR);
    }

    /**
     * Permet de donner des noms au url.
     *
     * @param $name
     * @return Application
     */
    public function named($name)
    {
        $this->namedRoute($this->current['path'], $name);
        return $this;
    }

    /**
     * d'active l'ecriture le l'entête X-Powered-By
     */
    public function disable_x_powered_by()
    {
        $this->disable_x_powered_by = true;
    }

    /**
     * REST API Maker.
     *
     * @param string $url
     * @param string|array $controllerName
     * @param array $where
     * @return $this
     * @throws ApplicationException
     */
    public function resources($url, $controllerName, array $where = [])
    {
        if (!is_string($controllerName) && !is_array($controllerName)) {
            throw new ApplicationException('Le premier paramètre doit être un array ou une chaine de caractère', 1);
        }

        $controller = '';
        $internalMiddleware = null;
        $ignoreMethod = [];
        $valideMethod = [
            [
                'url'    => '/',
                'call'   => 'index',
                'method' => 'get'
            ],
            [
                'url'    => '/',
                'call'   => 'store',
                'method' => 'post'
            ],
            [
                'url'    => '/:id',
                'call'   => 'show',
                'method' => 'get'
            ],
            [
                'url'    => '/:id',
                'call'   => 'update',
                'method' => 'put'
            ],
            [
                'url'    => '/:id',
                'call'   => 'destroy',
                'method' => 'delete'
            ],
            [
                'url'    => '/:id/edit',
                'call'   => 'edit',
                'method' => 'get'
            ],
            [
                'url'    => '/create',
                'call'   => 'create',
                'method' => 'get'
            ]
        ];

        if (is_array($controllerName)) {
            if (isset($controllerName['firewall'])) {
                $internalFirewall = $controllerName['firewall'];
                unset($controllerName['firewall']);

                $next = Actionner::call(
                    ['firewall' => $internalFirewall],
                    [$this->request],
                    $this->config['app.classes']
                );

                if ($next === false) {
                    return $this;
                }
            }

            if (isset($controllerName['uses'])) {
                $controller = $controllerName['uses'];
                unset($controllerName['uses']);
            }

            if (isset($controllerName['ignores'])) {
                $ignoreMethod = $controllerName['ignores'];
                unset($controllerName['ignores']);
            }
        } else  {
            $controller = $controllerName;
        }

        // normalize url
        $url = preg_replace('/\/+$/', '', $url);

        // Association de url prédéfinie
        foreach ($valideMethod as $key => $value) {
            // on vérifie si la methode de appelé est ignoré
            if (in_array($value['call'], $ignoreMethod)) {
                continue;
            }

            // Formate controlleur
            $bindController = $controller . '@' . $value['call'];
            $path = $url . $value['url'];

            $this->namedRoute(
                $path,
                strtolower(preg_replace('/controller/i', '', $controller)) . '.' . $value['call']
            );

            // Lancement de la methode de mapping de route.
            call_user_func_array(
                [$this, $value['method']],
                [rtrim($path, '/'), $bindController]
            );

            // Association des critères définies
            if (empty($where)) {
                continue;
            }

            $data = [];

            if (preg_match('/:id/', $path)) {
                if (isset($where['id'])) {
                    $data = $where;
                } else {
                    $data = ['id' => $where[0]];
                }
            }

            $this->where(array_merge($data, $where));
        }

        return $this;
    }

    /**
     * Ajout une route nommé.
     *
     * @param string $uri  L'url pointant sur la route.
     * @param string $name Le nom de la routes
     */
    private function namedRoute($uri, $name)
    {
        $routes = $this->config['app.routes'];
        $this->config['app.routes'] = array_merge($routes, [$name => $uri]);
    }

    /**
     * Retourne la listes des routes de l'application
     *
     * @return array Liste des routes définir dans l'application
     */
    public function getRoutes()
    {
        return $this->config['app.routes'];
    }

    /**
     * Retourne les définir pour une methode HTTP
     *
     * @param string $method
     * @return Route
     */
    public function getMethodRoutes($method)
    {
        return $this->routes[$method];
    }

    /**
     * Permet de lancer les middlewares par defaut
     */
    private function executeNativeFirewall()
    {
        $status = Actionner::firewall(ApplicationCsrfFirewall::class);

        if (!$status) {
            abort(500);
        }
    }

    /**
     * __call fonction magic php
     *
     * @param string $method
     * @param array $param
     *
     * @throws ApplicationException
     *
     * @return mixed
     */
    public function __call($method, array $param)
    {
        if (method_exists($this->config, $method)) {
            return call_user_func_array([$this->config, $method], $param);
        }

        if (in_array($method, $this->local)) {
            return call_user_func_array($this->local[$method], $param);
        }

        throw new ApplicationException('La methode ' . $method . ' n\'exist pas.', E_ERROR);
    }

    /**
     * Permet de récupérer la version de l'application
     *
     * @return string
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * @param $code
     * @param $message
     * @param array $headers
     * @throws HttpException
     */
    public function abort($code, $message = '', array $headers = [])
    {
        response()->statusCode($code);
        foreach ($headers as $key => $value) {
            response()->addHeader($key, $value);
        }
        if ($message == null) {
            $message = 'Le procéssus a été suspendu.';
        }
        throw new HttpException($message);
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        $code = http_response_code();

        if ($code == 404 || !isset($this->error_code[$code])) {
            return;
        }

        $this->response->statusCode($code);
        $r = call_user_func($this->error_code[$code]);

        $this->response->send($r);
    }
}