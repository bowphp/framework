<?php
namespace Bow\Application;

use Bow\Http\Response;
use Bow\Application\Exception\RouterException;

class Actionner
{
    /**
     * @var array All define namesapce
     */
    private static $names;

    /**
     * Lanceur de callback
     *
     * @param  callable|string|array $actions
     * @param  mixed                 $param
     * @param  array                 $names
     * @throws RouterException
     * @return mixed
     */
    public static function call($actions, $param = null, array $names = [], array $define_middlewares = [])
    {
        $param = is_array($param) ? $param : [$param];
        static::$names = $names;
        $functions = [];
        $middlewares = [];

        if (is_callable($actions)) {
            return call_user_func_array($actions, $param);
        }

        if (is_string($actions)) {
            $function = static::controller($actions);
            return call_user_func_array($function['controller'], array_merge($function['injections'], $param));
        }

        if (!is_array($actions)) {
            throw new \InvalidArgumentException('Le premier parametre doit etre un tableau, une chaine, une closure', E_USER_ERROR);
        }

        if (array_key_exists('middleware', $actions)) {
            $middlewares = (array) $actions['middleware'];
            unset($actions['middleware']);
        }

        foreach ($actions as $key => $action) {
            if ($key != 'uses' && !is_int($key)) {
                continue;
            }

            if (is_string($action)) {
                array_push($functions, static::controller($action));
                continue;
            }

            if (is_int($key)) {
                if (is_callable($action)) {
                    array_push($functions, $action);
                    continue;
                }

                if (is_string($action)) {
                    array_push($functions, static::controller($action));
                    continue;
                }
            }
        }

        // Status permettant de bloquer la suite du programme.
        $status = true;

        // Collecteur de middleware
        $middlewares_collection = [];
        $middlewares_guard = [];

        foreach ($middlewares as $middleware_alias) {

            if (class_exists($middleware_alias)) {
                $middlewares_collection[] = $middleware_alias;
                continue;
            }

            if (!array_key_exists($middleware_alias, $define_middlewares)) {
                throw new RouterException($middleware_alias . ' n\'est pas un middleware définir.', E_ERROR);
            }

            // On vérifie si le middleware définie est une middleware valide.
            if (!class_exists($define_middlewares[$middleware_alias])) {
                throw new RouterException($define_middlewares[$middleware_alias] . ' n\'est pas un class middleware.');
            }

            // Make middlewares collection
            $middlewares_collection[] = $define_middlewares[$middleware_alias];
            $parts = explode(':', $middleware_alias, 2);

            // Make guard collection
            if (count($parts) == 2) {
                $guard = $parts[1];
                $middlewares_guard[] = explode(',', $guard);
            } else {
                $middlewares_guard[] = null;
            }
        }

        $next = false;

        // Exécution du middleware
        foreach ($middlewares_collection as $key => $middleware) {
            $injections = static::injector($middleware, 'checker');

            $middleware_params = array_merge(
                $injections,
                [function () use (& $next) {
                    return $next = true;
                }, $middlewares_guard[$key]],
                $param
            );

            $status = call_user_func_array([new $middleware(), 'checker'], $middleware_params);

            if ($status === true && $next) {
                $next = false;
                continue;
            }

            if (($status instanceof \StdClass) || is_array($status) || (!($status instanceof Response))) {
                if (!empty($status)) {
                    die(json_encode($status));
                }
            }

            exit;
        }

        // Lancement de l'éxècution de la liste des actions definir
        // Fonction a éxècuté suivant un ordre
        if (!empty($functions)) {
            foreach ($functions as $function) {
                $status = call_user_func_array(
                    $function['controller'],
                    array_merge($function['injections'], $param)
                );
            }
        }

        return $status;
    }

    /**
     * Permet de lance un middleware
     *
     * @param  string   $middleware
     * @param  callable $callback
     * @return bool
     */
    public static function middleware($middleware, callable $callback = null)
    {
        $next = false;
        $injections = [];

        if (is_string($middleware) && class_exists($middleware)) {
            $instance = [new $middleware(), 'checker'];
            $injections = static::injector($middleware, 'checker');
        } else {
            $instance = $middleware;
        }

        $status = call_user_func_array(
            $instance,
            array_merge(
                $injections,
                [function () use (& $next) {
                    return $next = true;
                }]
            )
        );

        if (is_callable($callback)) {
            $callback();
        }

        return ($next && $status) === true;
    }

    /**
     * Permet de faire un injection
     *
     * @param  string $classname
     * @param  string $method
     * @return array
     */
    public static function injector($classname, $method)
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

            $class = trim($match[1]);

            if (class_exists($class, true)) {
                if (!in_array( strtolower($class), [
                    'string', 'array', 'bool', 'int',
                    'integer', 'double', 'float', 'callable',
                    'object', 'stdclass', '\closure', 'closure'])
                ) {
                    $params[] = new $class();
                }
            }
        }

        return $params;
    }

    /**
     * Next, lance successivement une liste de fonction.
     *
     * @param  array|callable $arr
     * @param  array|callable $arg
     * @return mixed
     */
    private static function exec($arr, $arg)
    {
        if (is_callable($arr)) {
            return call_user_func_array($arr, $arg);
        }

        if (is_array($arr)) {
            return call_user_func_array($arr, $arg);
        }

        // On lance la loader de controller si $cb est un String
        $controller = static::controller($arr);

        if (is_array($controller)) {
            return call_user_func_array(
                $controller['controller'],
                array_merge($controller['injections'], $arg)
            );
        }

        return false;
    }

    /**
     * Charge les controlleurs
     *
     * @param string $controllerName. Le nom du controlleur a utilisé
     *
     * @return array
     */
    private static function controller($controllerName)
    {
        // Récupération de la classe et de la methode à lancer.
        if (is_null($controllerName)) {
            return null;
        }

        list($class, $method) = preg_split('/\.|@|::|->/', $controllerName);
        $class = static::$names['controller'] . '\\' . ucfirst($class);

        $injections = static::injector($class, $method);

        return [
            'controller' => [new $class(), $method],
            'injections' => $injections
        ];
    }
}
