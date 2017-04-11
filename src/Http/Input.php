<?php
namespace Bow\Http;

use Closure;
use ErrorException;
use Bow\Support\Collection;
use Bow\Validation\Validate;
use Bow\Validation\Validator;
use Bow\Interfaces\CollectionAccess;

/**
 * Class RequestData
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Http
 */
class Input implements CollectionAccess
{

    /**
     * @var array
     */
    private $data = [];

    /**
     * Fonction magic __clone en <<private>>
     */
    private function __clone()
    {
    }

    public function __construct()
    {
        $this->data = array_merge($_POST, $_GET);
    }

    /**
     * has, vérifie l'existance d'une clé dans la colléction
     *
     * @param string $key
     * @param bool $strict
     *
     * @return boolean
     */
    public function has($key, $strict = false)
    {
        if ($strict) {
            return isset($this->data[$key]) && !empty($this->data[$key]);
        }
        return isset($this->data[$key]);
    }

    /**
     * isEmpty, vérifie si une collection est vide.
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

    /**
     * get, permet de récupérer une valeur ou la colléction de valeur.
     *
     * @param string $key =null
     * @param mixed $default =false
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }

    /**
     * get, permet de récupérer une valeur ou la colléction de valeur.
     *
     * @param array|string|int $expects
     * @return mixed
     */
    public function getWithOut($expects)
    {
        $data = [];

        if (!is_array($expects)) {
            $keyWasDefine = $expects;
        } else {
            $keyWasDefine = func_get_args();
        }

        foreach ($this->data as $key => $value) {
            if (!in_array($key, $keyWasDefine)) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * vérifie si le contenu de $this->data poccedent la $key n'est pas vide.
     *
     * @param string $key
     * @param string $eqTo
     *
     * @return bool
     */
    public function isValid($key, $eqTo = null)
    {
        $boolean = $this->has($key, true);

        if ($eqTo && $boolean) {
            $boolean = $boolean && preg_match("~$eqTo~", $this->get($key));
        }

        return $boolean;
    }

    /**
     * remove, supprime une entrée dans la colléction
     *
     * @param string $key
     *
     * @return Input
     */
    public function remove($key)
    {
        throw new \RuntimeException("Method 'add' not exists");
    }

    /**
     * add, ajoute une entrée dans la colléction
     *
     * @param string $key
     * @param mixed $data
     * @param bool $next
     *
     * @return Input
     */
    public function add($key, $data, $next = false)
    {
        throw new \RuntimeException("Method 'add' not exists");
    }

    /**
     * set, modifie une entrée dans la colléction
     *
     * @param string $key
     * @param mixed $value
     *
     * @throws ErrorException
     *
     * @return Input
     */
    public function set($key, $value)
    {
        throw new \RuntimeException("Method 'set' not exists");
    }

    /**
     * each, parcourir les entrées de la colléction
     *
     * @param Closure $cb
     */
    public function each(Closure $cb)
    {
        if (! $this->isEmpty()) {
            foreach ($this->data as $key => $value) {
                call_user_func_array($cb, [$value, $key]);
            }
        }
    }

    /**
     * __get
     *
     * @param string $name Le nom de la variable
     * @return null
     */
    public function __get($name)
    {
        if ($this->has($name)) {
            return $this->data[$name];
        }

        return null;
    }

    /**
     * Alias sur toArray
     */
    public function all()
    {
        return $this->toArray();
    }

    /**
     * @param $method
     * @return array
     */
    public function method($method)
    {
        if ($method == "GET") {
            return $_GET;
        }
        if ($method == "POST") {
            return $_POST;
        }
        return [];
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return (array) $this->data;
    }

    /**
     * @inheritdoc
     */
    public function toObject()
    {
        return (object) $this->data;
    }

    /**
     * Permet de valider les données entrantes
     *
     * @param array $rule
     * @return  Validate
     */
    public function validate(array $rule)
    {
        return Validator::make($this->data, $rule);
    }

    /**
     * Retourne une instance de la classe collection.
     *
     * @return Collection
     */
    public function toCollection()
    {
        return new Collection($this->data);
    }

    /**
     * __callStatic
     *
     * @param string $name
     * @param array $argmunents
     *
     * @return mixed
     */
    public static function __callStatic($name, $argmunents)
    {
        if (! method_exists(static::class, $name)) {
            throw new \RuntimeException('Method '. $name . ' not exists');
        }
        return call_user_func_array([static::class, $name], $argmunents);
    }
}
