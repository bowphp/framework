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
     * @param callable|string|array $actions
     * @param mixed $param
     * @param array $names
     * @throws RouterException
     * @return mixed
     */
    public static function call($actions, $param = null, array $names = [])
    {
        $param = is_array($param) ? $param : [$param];
        $functions = [];

        if (!isset($names['namespace'])) {
            return static::exec($actions, $param);
        }

        static::$names = $names;

        if (!isset($names['namespace'])) {
            throw new RouterException('Le namespace d\'autoload n\'est pas défini dans le fichier de configuration');
        }

        $firewalls = [];

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

        if (array_key_exists('firewall', $actions)) {
            $firewalls = $actions['firewall'];
            unset($actions['firewall']);
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

            if (isset($action['with']) && isset($action['call'])) {
                if (is_string($action['call'])) {
                    $controller = $action['with'].'@'.$action['call'];
                    array_push($functions, static::controller($controller));
                    continue;
                }

                foreach($action['call'] as $method) {
                    $controller = $action['with'].'@'.$method;
                    array_push($functions,  static::controller($controller));
                }
                continue;
            }
        }

        // Status permettant de bloquer la suite du programme.
        $status = true;

        if (!is_array($firewalls)) {
            $firewalls = [$firewalls];
        }

        // Collecteur de firewall
        $firewalls_collection = [];
        $firewalls_guard = [];

        foreach ($firewalls as $firewall) {
            if (!is_string($firewall)) {
                continue;
            }

            if (class_exists($firewall)) {
                $firewalls_collection[] = $firewall;
                continue;
            }

            if (!array_key_exists($firewall, $names['firewalls'])) {
                throw new RouterException($firewall . ' n\'est pas un firewall définir.', E_ERROR);
            }

            // On vérifie si le firewall définie est une firewall valide.
            if (!class_exists($names['firewalls'][$firewall])) {
                throw new RouterException($names['firewalls'][$firewall] . ' n\'est pas un class firewall.');
            }

            // Make firewalls collection
            $firewalls_collection[] = $names['firewalls'][$firewall];
            $parts = explode(':', $firewall, 2);

            // Make guard collection
            if (count($parts) == 2) {
                $guard = $parts[1];
                $firewalls_guard[] = $guard;
            } else {
                $firewalls_guard[] = null;
            }
        }

        $next = false;
        // Exécution du firewall
        foreach ($firewalls_collection as $key => $firewall) {
            $injections = static::injector($firewall, 'checker');

            $firewall_params = array_merge($injections, [function () use (& $next) {
                return $next = true;
            }, $firewalls_guard[$key]], $param);

            $status = call_user_func_array([new $firewall(), 'checker'], $firewall_params);

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
            foreach($functions as $function) {
                $status = call_user_func_array(
                    $function['controller'],
                    array_merge($function['injections'], $param)
                );
            }
        }

        return $status;
    }

    /**
     * Permet de lance un firewall
     *
     * @param string $firewall
     * @param callable $callback
     * @return bool
     */
    public static function firewall($firewall, callable $callback = null)
    {
        $next = false;
        $injections = [];

        if (is_string($firewall) && class_exists($firewall)) {
            $instance = [new $firewall(), 'checker'];
            $injections = static::injector($firewall, 'checker');
        } else {
            $instance = $firewall;
        }

        $status = call_user_func_array($instance, array_merge($injections, [function () use (& $next) {
            return $next = true;
        }]));

        if (is_callable($callback)) {
            $callback();
        }

        return ($next && $status) === true;
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
                if (!in_array(strtolower($class), [
                    'string', 'array', 'bool', 'int',
                    'integer', 'double', 'float', 'callable',
                    'object', 'stdclass', '\closure', 'closure'
                ])) {
                    $params[] = new $class();
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
        $class = static::$names['namespace']['controller'] . '\\' . ucfirst($class);

        $injections = static::injector($class, $method);

        return [
            'controller' => [new $class(), $method],
            'injections' => $injections
        ];
    }
}