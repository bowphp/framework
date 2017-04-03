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
     * @param callable $cb
     * @param mixed $param
     * @param array $names
     * @throws RouterException
     * @return mixed
     */
    public static function call($cb, $param = null, array $names = [])
    {
        $param = is_array($param) ? $param : [$param];
        $function_list = [];

        if (!isset($names['namespace'])) {
            return static::exec($cb, $param);
        }

        static::$names = $names;

        if (!file_exists($names['autoload'] . '.php')) {
            throw new RouterException('L\'autoload n\'est pas défini dans le fichier de configuration', E_ERROR);
        }


        if (!isset($names['namespace']['app'])) {
            throw new RouterException('Le namespace d\'autoload n\'est pas défini dans le fichier de configuration');
        }

        $middleware = null;

        if (is_callable($cb)) {
            return call_user_func_array($cb, $param);
        }

        if (is_string($cb)) {
            return call_user_func_array(static::controller($cb), $param);
        }

        if (is_array($cb)) {
            if (array_key_exists('middleware', $cb)) {
                $middleware = $cb['middleware'];
                unset($cb['middleware']);
            }

            if (array_key_exists('uses', $cb)) {
                if (is_array($cb['uses'])) {
                    if (isset($cb['uses']['with']) && isset($cb['uses']['call'])) {
                        if (is_string($cb['uses']['call'])) {
                            $controller = $cb['uses']['with'] . '@' . $cb['uses']['call'];
                            array_push($function_list, static::controller($controller));
                        } else {
                            foreach($cb['uses']['call'] as $method) {
                                $controller = $cb['uses']['with'] . '@' . $method;
                                array_push($function_list,  static::controller($controller));
                            }
                        }
                    } else {
                        foreach($cb['uses'] as $controller) {
                            if (is_string($controller)) {
                                array_push($function_list,  static::controller($controller));
                            } else if (is_callable($controller)) {
                                array_push($function_list, $controller);
                            }
                        }
                    }
                } else {
                    if (is_string($cb['uses'])) {
                        array_push($function_list, static::controller($cb['uses']));
                    } else {
                        array_push($function_list, $cb['uses']);
                    }
                }

                unset($cb['uses']);
            }

            if (count($cb) > 0) {
                foreach($cb as $func) {
                    if (is_callable($func)) {
                        array_push($function_list, $func);
                    } else if (is_string($func)) {
                        array_push($function_list, static::controller($func));
                    }
                }
            }
        }

        // Status permettant de bloquer la suite du programme.
        $status = true;

        // Execution du middleware si define.
        if (is_string($middleware)) {
            if (! array_key_exists(ucfirst($middleware), $names['middlewares'])) {
                throw new RouterException($middleware . ' n\'est pas un middleware définir.', E_ERROR);
            }
            // Chargement du middleware
            $classMiddleware = $names['namespace']['middleware'] . '\\' . ucfirst($names['middlewares'][$middleware]);
            // On vérifie si le middleware définie est une middleware valide.
            if (! class_exists($classMiddleware)) {
                throw new RouterException($middleware . ' n\'est pas un class middleware.');
            }

            $instance = new $classMiddleware();
            $handler = [$instance, 'handle'];
            $status = call_user_func_array($handler, $param);

            // Le middleware est un callback. les middleware peuvent être// définir comme des callback par l'utilisteur
        } else if (is_callable($middleware)) {
            $status = call_user_func_array($middleware, $param);
        }

        // On arrêt tout en case de status false.
        if ($status == false) {
            return false;
        }

        // Lancement de l'execution de la liste
        // fonction a execute suivant un ordre
        // conforme au middleware.
        if (!empty($function_list)) {
            $status = true;

            foreach($function_list as $func) {
                $status = call_user_func_array($func, $param);
                if ($status == false) {
                    return $status;
                }
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