<?php

namespace Bow\Application;

use Bow\Http\Response;
use Bow\Application\Exception\RouterException;

class Actionner
{
    /**
     * La liste des namespaces défini dans l'application
     *
     * @var array
     */
    private $namespaces;

    /**
     * La liste de middleware charge dans l'application
     *
     * @var array
     */
    private $middlewares;

    /**
     * @var Actionner
     */
    private static $instance;

    /**
     * Liste de guard
     *
     * @var array
     */
    private $guards = [];

    /**
     * Actionner constructor
     *
     * @param array $namespaces
     * @param array $middlewares
     */
    public function __construct(array $namespaces, array $middlewares)
    {
        $this->namespaces = $namespaces;

        $this->middlewares = $middlewares;
    }

    /**
     * Configuration de l'actionneur
     *
     * @param array $namespaces
     * @param array $middlewares
     * @return static
     */
    public static function configure(array $namespaces, array $middlewares)
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($namespaces, $middlewares);
        }

        return static::$instance;
    }

    /**
     * Récupère une instance de l'actonneur
     *
     * @return Actionner
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Ajout un middleware à la liste
     *
     * @param array|callable $middlewares
     * @param bool $end
     */
    public function pushMiddleware($middlewares, $end = false)
    {
        $middlewares = (array) $middlewares;

        if ($end) {
            array_merge($this->middlewares, $middlewares);
        } else {
            array_merge($middlewares, $this->middlewares);
        }
    }

    /**
     * Ajout un namespace à la liste
     *
     * @param array|string $namespace
     */
    public function pushNamespace($namespace)
    {
        $namespace = (array) $namespace;

        $this->namespaces = array_merge($this->namespaces, $namespace);
    }

    /**
     * Lanceur de callback
     *
     * @param  callable|string|array $actions
     * @param  mixed  $param
     * @return mixed
     *
     * @throws RouterException
     */
    public function call($actions, $param = null)
    {
        $param = (array) $param;

        $functions = [];

        $middlewares = [];

        if (is_callable($actions)) {
            return call_user_func_array($actions, $param);
        }

        if (is_string($actions)) {
            $function = $this->controller($actions);

            return call_user_func_array($function['controller'], array_merge($function['injections'], $param));
        }

        if (!is_array($actions)) {
            throw new \InvalidArgumentException('Le premier paramètre doit être un tableau, une chaine ou une closure', E_USER_ERROR);
        }

        if (array_key_exists('middleware', $actions)) {
            $middlewares = (array) $actions['middleware'];

            unset($actions['middleware']);
        }

        if (isset($actions['uses'])) {
            $actions = (array) $actions['uses'];
        }

        foreach ($actions as $key => $action) {
            if (is_string($action)) {
                array_push($functions, $this->controller($action));

                continue;
            }

            if (is_callable($action)) {
                $injections = $this->injectorForClosure($action);

                array_push($functions, ['controller' => $action, 'injections' => $injections]);
            }
        }

        // Status permettant de bloquer la suite du programme.
        $status = true;

        // Collecteur de middleware
        $middlewares_collection = [];

        foreach ($middlewares as $middleware) {
            if (class_exists($$middleware)) {
                $middlewares_collection[] = $middleware;

                continue;
            }

            if (!array_key_exists($middleware, $this->middlewares)) {
                throw new RouterException(sprintf('%s n\'est pas un middleware définir.', $middleware), E_ERROR);
            }

            // On vérifie si le middleware définie est une middleware valide.
            if (!class_exists($this->middlewares[$middleware])) {
                throw new RouterException(sprintf('%s n\'est pas un class middleware.', $middleware));
            }

            // Make middlewares collection
            $middlewares_collection[] = $this->middlewares[$middleware];

            $parts = explode(':', $middleware, 2);

            // Make guard collection
            if (count($parts) == 2) {
                $this->pushGuard(explode(',', end($parts)));
            } else {
                $this->pushGuard(null);
            }
        }

        $next = false;

        // Exécution du middleware
        foreach ($middlewares_collection as $key => $middleware) {
            $injections = $this->injector($middleware, 'checker');

            $middleware_params = array_merge(
                $injections,
                [$this->next($next), $this->guards[$key]],
                $param
            );

            $status = call_user_func_array([new $middleware(), 'checker'], $middleware_params);

            if ($status === true && $next) {
                $next = false;

                continue;
            }

            // Envoi de réponse en json
            if (($status instanceof \StdClass) || is_array($status) || (!($status instanceof Response))) {
                if (!empty($status)) {
                    die(json_encode($status));
                }
            }

            exit;
        }

        
        // Lancement de l'éxècution de la liste des actions definir
        // Fonction a éxècuté suivant un ordre
        foreach ($functions as $function) {
            $status = call_user_func_array(
                $function['controller'],
                array_merge($function['injections'], $param)
            );
        }

        return $status;
    }

    /**
     * Permet de lancer un middleware
     *
     * @param string $middleware
     * @param callable $callback
     * @return bool
     */
    public function middleware($middleware, callable $callback = null)
    {
        $next = false;

        $injections = [];

        if (is_string($middleware) && class_exists($middleware)) {
            $instance = [new $middleware(), 'checker'];

            $injections = $this->injector($middleware, 'checker');
        } else {
            $instance = $middleware;
        }

        $status = call_user_func_array(
            $instance,
            array_merge($injections, [$this->next($next)])
        );

        if (is_callable($callback)) {
            $callback();
        }

        return ($next && $status) === true;
    }

    /**
     * Lancement de middleware suivant
     *
     * @param mixed $next
     * @return callable
     */
    private function next(& $next)
    {
        return function ($request = null) use (& $next) {
            return $next = true;
        };
    }

    /**
     * Permet de faire un injection
     *
     * @param string $classname
     * @param string $method
     * @return array
     * @throws
     */
    public function injector($classname, $method)
    {
        $params = [];
        $reflection = new \ReflectionClass($classname);

        $parts = preg_split(
            '/(\n|\*)+/',
            $reflection->getMethod($method)->getDocComment()
        );

        foreach ($parts as $value) {
            if (!preg_match('/^@param\s+(.+)\s+\$/', trim($value), $match)) {
                continue;
            }

            $class = trim(end($match));

            if (! class_exists($class, true)) {
                continue;
            }

            if (!in_array(strtolower($class), $this->getInjectorExceptedType())) {
                $params[] = new $class();
            }
        }

        return $params;
    }

    /**
     * Injection de type pour closure
     *
     * @param callable $closure
     * @return array
     * @throws
     */
    public function injectorForClosure(callable $closure)
    {
        $reflection = new \ReflectionFunction($closure);
        $parameters = $reflection->getParameters();
        $params = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            
            if (is_null($type)) {
                continue;
            }

            $class = trim($type->getName());

            if (! class_exists($class, true)) {
                continue;
            }

            if (!in_array(strtolower($class), $this->getInjectorExceptedType())) {
                $params[] = new $class();
            }
        }

        return $params;
    }

    /**
     * La liste de type non permis
     *
     * @return array
     */
    private function getInjectorExceptedType()
    {
        return [
            'string', 'array', 'bool', 'int',
            'integer', 'double', 'float', 'callable',
            'object', 'stdclass', '\closure', 'closure'
        ];
    }

    /**
     * Next, lance successivement une liste de fonction.
     *
     * @param array|callable $arr
     * @param array|callable $arg
     * @return mixed
     */
    public function execute($arr, $arg)
    {
        if (is_callable($arr)) {
            return call_user_func_array($arr, $arg);
        }

        if (is_array($arr)) {
            return call_user_func_array($arr, $arg);
        }

        // On lance la loader de controller si $cb est un String
        $controller = $this->controller($arr);

        if ($controller['controller'][1] == null) {
            array_splice($controller['controller'], 1, 1);
        }

        if (is_array($controller)) {
            return call_user_func_array(
                $controller['controller'],
                array_merge($controller['injections'], $arg)
            );
        }

        return false;
    }

    /**
     * Charge les controleurs definie comme chaine de caractère
     *
     * @param string $controller_name
     *
     * @return array
     */
    public function controller($controller_name)
    {
        // Récupération de la classe et de la methode à lancer.
        if (is_null($controller_name)) {
            return null;
        }

        $parts = preg_split('/\.|@|::|->/', $controller_name);

        if (count($parts) == 1) {
            $parts[1] = null;
        }

        list($class, $method) = $parts;

        $class = sprintf('%s\\%s', $this->namespaces['controller'], ucfirst($class));

        $injections = $this->injector($class, $method);

        return [
            'controller' => [new $class(), $method],
            'injections' => $injections
        ];
    }

    /**
     * Ajout de guard
     *
     * @param $guard
     */
    public function pushGuard($guard)
    {
        $this->guards[] = $guard;
    }
}
