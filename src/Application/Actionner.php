<?php
namespace Bow\Application;

use Bow\Http\Response;
use Bow\Exception\RouterException;

class Actionner
{
    /**
     * @var array All define namesapce
     */
    private static $names;

    /**
     * Lanceur de callback
     *
     * @param callable|string|array $actions
     * @param mixed $param
     * @param array $names
     * @throws RouterException
     * @return mixed
     */
    public static function call($actions, $param = null, array $names = [])
    {
        $param = is_array($param) ? $param : [$param];
        $function_list = [];

        if (! isset($names['namespace'])) {
            return static::exec($actions, $param);
        }

        static::$names = $names;

        if (! isset($names['namespace'])) {
            throw new RouterException('Le namespace d\'autoload n\'est pas défini dans le fichier de configuration');
        }

        $middlewares = [];

        if (is_callable($actions)) {
            return call_user_func_array($actions, $param);
        }

        if (is_string($actions)) {
            return call_user_func_array(static::controller($actions), $param);
        }

        if (! is_array($actions)) {
            throw new \InvalidArgumentException('Le premier parametre doit etre un tableau, une chaine, une closure', E_USER_ERROR);
        }

        if (array_key_exists('middleware', $actions)) {
            $middlewares = $actions['middleware'];
            unset($actions['middleware']);
        }

        foreach ($actions as $key => $action) {
            if ($key != 'uses' || !is_int($key)) {
                continue;
            }

            if (is_int($key)) {
                if (is_callable($action)) {
                    array_push($function_list, $action);
                    continue;
                }
                if (is_string($action)) {
                    array_push($function_list, static::controller($action));
                    continue;
                }
            }

            if (isset($action['with']) && isset($action['call'])) {
                if (is_string($action['call'])) {
                    $controller = $action['with'].'@'.$action['call'];
                    array_push($function_list, static::controller($controller));
                    continue;
                }
                foreach($action['call'] as $method) {
                    $controller = $action['with'].'@'.$method;
                    array_push($function_list,  static::controller($controller));
                }
                continue;
            }
        }

        // Status permettant de bloquer la suite du programme.
        $status = true;

        if (! is_array($middlewares)) {
            $middlewares = [$middlewares];
        }

        // Collecteur de middleware
        $middlewares_collection = [];

        foreach ($middlewares as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }
            if (! array_key_exists($middleware, $names['middlewares'])) {
                throw new RouterException($middleware . ' n\'est pas un middleware définir.', E_ERROR);
            }
            // Chargement du middleware
            $class_middleware = $names['namespace']['middleware'] . '\\' . ucfirst($names['middlewares'][$middleware]);
            // On vérifie si le middleware définie est une middleware valide.
            if (! class_exists($class_middleware)) {
                throw new RouterException($middleware . ' n\'est pas un class middleware.');
            }
            $middlewares_collection[] = $class_middleware;
        }

        $next = false;
        // Exécution du middleware
        foreach ($middlewares_collection as $middleware) {
            $instances = static::injector($middleware, 'handle');
            $status = call_user_func_array([new $middleware(), 'handle'], array_merge($instances, [function () use (& $next) {
                return $next = true;
            }], $param));
            if ($status === true && $next) {
                $next = false;
                continue;
            }
            if (($status instanceof \StdClass) || is_array($status) || (!($status instanceof Response))) {
                if (! empty($status)) {
                    die(json_encode($status));
                }
            }
            die();
        }

        // Lancement de l'execution de la liste
        // fonction a execute suivant un ordre
        // conforme au middleware.
        if (! empty($function_list)) {
            foreach($function_list as $func) {
                $status = call_user_func_array($func, $param);
            }
        }
        return $status;
    }

    /**
     * Permet de lance un middleware
     *
     * @param string $middleware
     * @param array $param
     * @return bool
     */
    public static function middleware($middleware, $param)
    {
        $instance = new $middleware();
        $next = false;
        $status = call_user_func_array([$instance, 'handle'], array_merge([function () use ($next) {
            return $next = true;
        }], $param));
        return $next && $status;
    }

    /**
     * Permet de faire un injection
     *
     * @param string $classname
     * @param string $method
     * @return array
     */
    public static function injector($classname, $method)
    {
        $params = [];
        $reflection = new \ReflectionClass($classname);
        $parts = preg_split('/(\n|\*)+/', $reflection->getMethod($method)->getDocComment());
        foreach ($parts as $value) {
            if (preg_match('/^@param\s+(.+)/', trim($value), $match)) {
                $param = preg_split('/\s+/', $match[1]);
                if (class_exists($param[0], true)) {
                    if (! in_array(strtolower($param[0]), ['\closure', 'closure', 'string', 'array', 'bool', 'int', 'integer', 'object', 'stdclass'])) {
                        $params[] = new $param[0]();
                    }
                }
            }
        }
        return $params;
    }

    /**
     * Next, lance successivement une liste de fonction.
     *
     * @param array|callable $arr
     * @param array|callable $arg
     * @return mixed
     */
    private static function exec($arr, $arg)
    {
        if (is_callable($arr)) {
            return call_user_func_array($arr, $arg);
        }

        if (! is_array($arr)) {
            // On lance la loader de controller si $cb est un String
            $cb = static::controller($arr);

            if ($cb !== null) {
                return call_user_func_array($cb, $arg);
            }

            return null;
        }

        // Lancement de la procedure de lancement recursive.
        return array_reduce($arr, function($next, $cb) use ($arg) {
            // $next est-il null
            if (is_null($next)) {
                // On lance la loader de controller si $cb est un String
                if (is_string($cb)) {
                    $cb = static::controller($cb);
                }

                return call_user_func_array($cb, $arg);
            }

            // $next est-il a true.
            if ($next !== true) {
                die();
            }

            // On lance la loader de controller si $cb est un String
            if (is_string($cb)) {
                $cb = static::controller($cb);
            }

            return call_user_func_array($cb, $arg);
        });
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

        list($class, $method) = preg_split('/\.|@/', $controllerName);
        $class = static::$names['namespace']['controller'] . '\\' . ucfirst($class);

        return [new $class(), $method];
    }
}