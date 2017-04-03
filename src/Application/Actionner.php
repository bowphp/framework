<?php
namespace Bow\Application;

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

        if (! file_exists($names['autoload'] . '.php')) {
            throw new RouterException('L\'autoload n\'est pas défini dans le fichier de configuration', E_ERROR);
        }

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

        $middlewares_collection = [];

        foreach ($middlewares as $middleware) {
            if (is_callable($middleware)) {
                $middlewares_collection[] = $middleware;
                continue;
            }
            // Execution du middleware si define.
            if (is_string($middleware)) {
                if (! array_key_exists($middleware, $names['middlewares'])) {
                    throw new RouterException($middleware . ' n\'est pas un middleware définir.', E_ERROR);
                }
                // Chargement du middleware
                $class_middleware = $names['namespace']['middleware'] . '\\' . ucfirst($names['middlewares'][$middleware]);
                // On vérifie si le middleware définie est une middleware valide.
                if (! class_exists($class_middleware)) {
                    throw new RouterException($middleware . ' n\'est pas un class middleware.');
                }

                if (! array_key_exists($class_middleware, $middlewares_collection)) {
                    $instance = new $class_middleware();
                    $middlewares_collection[$class_middleware] = [$instance, 'handle'];
                }
            }
        }

        foreach ($middlewares_collection as $middleware) {
            $status = call_user_func_array($middleware, array_merge([function () {
                return true;
            }], $param));
            if ($status === true) {
                continue;
            }
            if (is_callable($status)) {
                $status = $status();
            }
            if (is_object($status) || is_array($status)) {
                $status = json_encode($status);
            }
            die($status);
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