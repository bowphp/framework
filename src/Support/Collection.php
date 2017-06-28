<?php
namespace Bow\Support;

/**
 * Classe de la manipulation de donnés dans un tableau
 *
 * @class Collection
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class Collection implements \Countable, \JsonSerializable, \IteratorAggregate, \ArrayAccess
{
    /**
     * @var array
     */
    protected $storage = [];

    /**
     * Constructeur d'instance.
     *
     * @param array $arr
     */
    public function __construct(array $arr = [])
    {
        $this->storage = $arr;
    }

    /**
     * Le premier element de la liste
     *
     * @return mixed
     */
    public function first()
    {
        return current($this->storage);
    }

    /**
     * Le dernier element de la liste
     *
     * @return array
     */
    public function last()
    {
        $element = end($this->storage);
        reset($this->storage);
        return $element;
    }

    /**
     * has, vérifie l'existance une clé dans la colléction de session
     *
     * @param string $key La clé de l'élément récherché
     * @param bool   $strict Quand $val est a true alors :has vas vérifie $key non pas comment une cle mais un valeur.
     * @return boolean
     */
    public function has($key, $strict = false)
    {
        // Quand $strict est a true alors :has vas vérifie
        // $key non pas comment une cle mais un valeur.
        $isset = isset($this->storage[$key]);
        if ($isset) {
            if ($strict === true) {
                $isset = $isset && !empty($this->storage[$key]);
            }
        }

        return $isset;
    }

    /**
     * isEmpty, vérifie si une colléction est vide.
     *
     * @return boolean
     */
    public function isEmpty()
    {
        $isEmpty = empty($this->storage);

        if ($isEmpty === false) {
            if ($this->length() == 1) {
                if (is_null($this->values()[0])) {
                    $isEmpty = true;
                }
            }
        }

        return $isEmpty;
    }

    /**
     * get, permet de récupérer une valeur ou la colléction de valeur.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->storage[$key] == null ? $default : $this->storage[$key];
        }

        if ($default !== null) {
            if (is_callable($default)) {
                return call_user_func($default);
            } else {
                return $default;
            }
        }

        return null;
    }

    /**
     * retourne la liste des valeurs de la collection
     *
     * @return Collection
     */
    public function values()
    {
        $r = [];

        foreach($this->storage as $value) {
            array_push($r, $value);
        }

        return new Collection($r);
    }

    /**
     * retourne la liste des clés de la collection
     * @return Collection
     */
    public function keys()
    {
        $r = [];

        foreach($this->storage as $key => $value) {
            array_push($r, $key);
        }

        return new Collection($r);
    }

    /**
     * Quand on appelera la fonction count sur un object collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->storage);
    }

    /**
     * collectionify, permet de récupérer une valeur ou la colléction de valeur sous forme
     * d'instance de collection.
     *
     * @param string $key La clé de l'élément
     *
     * @return Collection
     */
    public function collectionify($key)
    {
        $data = [];
        if ($this->has($key)) {
            $data = $this->storage[$key];
            if (!is_array($data)) {
                $data = [$data];
            }
        }
        return new Collection($data);
    }

    /**
     * delete, supprime une entrée dans la colléction
     *
     * @param string $key
     *
     * @return Collection
     */
    public function delete($key)
    {
        unset($this->storage[$key]);
        return $this;
    }

    /**
     * set, modifie une entrée dans la colléction ou l'ajout si non
     *
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    public function set($key, $value)
    {
        if ($this->has($key)) {
            $old = $this->storage[$key];
            $this->storage[$key] = $value;
            return $old;
        }

        $this->storage[$key] = $value;

        return null;
    }

    /**
     * each parcour l'ensemble des valeurs de la collection
     *
     * @param callable $cb
     *
     * @return Collection
     */
    public function each(Callable $cb)
    {
        foreach ($this->storage as $key => $value) {
            call_user_func_array($cb, [$value, $key]);
        }

        return $this;
    }

    /**
     * fusion la collection avec un tableau ou une autre collection
     * @param Collection|array $array
     *
     * @throws \ErrorException
     *
     * @return Collection
     */
    public function merge($array) {

        if (is_array($array)) {
            $this->storage = array_merge($this->storage, $array);
        } else if ($array instanceof Collection) {
            $this->storage = array_merge($this->storage, $array->toArray());
        } else {
            throw new \ErrorException(__METHOD__ . '(), must be take 1 parameter to be array or Collection, ' . gettype($array) . ' given', E_ERROR);
        }

        return $this;
    }
    /**
     * map
     *
     * @param callable $cb
     *
     * @return Collection
     */
    public function map($cb)
    {
        $data = $this->storage;

        foreach ($data as $key => $value) {
            $data[$key] = call_user_func_array($cb, [$value, $key]);
        }

        return new Collection($data);
    }

    /**
     * filter
     *
     * @param callable $cb
     *
     * @return Collection
     */
    public function filter($cb)
    {
        $r = [];

        foreach ($this->storage as $key => $value) {
            if (call_user_func_array($cb, [$value, $key])) {
                $r[] = $this->storage[$key];
            }
        }

        return new Collection($r);
    }

    /**
     * Fill
     *
     * @param mixed $data
     * @param int $offset
     *
     * @return array
     */
    public function fill($data, $offset)
    {
        $old = $this->storage;
        $len = count($old);

        for($i = $len, $len += $offset; $i < $len; $i++) {
            $this->storage[$i] = $data;
        }

        return $old;
    }

    /**
     * reduce
     *
     * @param callable $cb
     * @param mixed $next
     *
     * @return self
     */
    public function reduce($cb, $next = null)
    {
        foreach ($this->storage as $key => $current) {
            $next = call_user_func_array($cb, [$next, $current, $key, $this->storage]);
        }

        return $this;
    }

    /**
     * Implode
     *
     * @param $sep
     * @return string
     */
    public function implode($sep)
    {
        return implode($sep, $this->toArray());
    }

    /**
     * Sum
     *
     * @param callable $cb
     *
     * @return int
     */
    public function sum($cb = null)
    {
        $sum = 0;

        $this->recursive($this->storage, function($value) use (& $sum) {
            if (is_numeric($value)) {
                $sum += $value;
            }
        });

        if ($cb !== null) {
            call_user_func_array($cb, [$sum]);
        }

        return $sum;
    }

    /**
     * Max
     *
     * @param callable $cb
     *
     * @return number
     */
    public function max($cb = null)
    {
        return $this->aggregate($cb, 'max');
    }

    /**
     * Max
     *
     * @param callable $cb
     *
     * @return number
     */
    public function min($cb = null)
    {
        return $this->aggregate($cb, 'min');
    }

    /**
     * aggregate Execute max|min
     *
     * @param callable $cb
     * @param string $type
     *
     * @return number
     */
    private function aggregate($cb = null, $type)
    {
        $data = [];

        $this->recursive($this->storage, function($value) use (& $data) {
            if (is_numeric($value)) {
                $data[] = $value;
            }
        });

        $r = call_user_func_array($type, $data);

        if (is_callable($cb)) {
            call_user_func_array($cb, [$r]);
        }

        return $r;
    }

    /**
     * Permet de retourne la liste de clé
     * et retourne une instance de Collection.
     *
     * @param array $except Liste des éléments à ignorer
     * @return Collection
     */
    public function excepts(array $except)
    {
        $data = [];

        $this->recursive($this->storage, function($value, $key) use (& $data, $except) {
            if (in_array($key, $except)) {
                $data[$key] = $value;
            }
        });

        return new Collection($data);
    }

    /**
     * Permet d'ignorer la clé que l'on lui donne
     * et retourne une instance de Collection.
     *
     * @param array $ignores Liste des éléments à ignorer
     * @return Collection
     */
    public function ignores(array $ignores)
    {
        $data = [];

        $this->recursive($this->storage, function($value, $key) use (& $data, $ignores) {
            if (!in_array($key, $ignores)) {
                $data[$key] = $value;
            }
        });

        return new Collection($data);
    }

    /**
     * reverse
     *
     * @return Collection
     */
    public function reverse()
    {
        return new Collection(array_reverse($this->storage));
    }

    /**
     * update, met à jour une valeur existant dans la collection
     *
     * @param string|integer $key
     * @param mixed $data
     * @param boolean $overide
     * @return boolean
     */
    public function update($key, $data, $overide = false)
    {
        if (!$this->has($key)) {
            return false;
        }

        if (!is_array($this->storage[$key]) || $overide === true) {
            $this->storage[$key] = $data;
            return true;
        }

        if (!is_array($data)) {
            $data = [$data];
        }

        $this->storage[$key] = array_merge($this->storage[$key], $data);
        return true;
    }

    /**
     * yieldify, lance un générateur
     *
     * @return \Generator
     */
    public function yieldify()
    {
        foreach ($this->storage as $key => $value) {
            yield (object) ['value' => $value, 'key' => $key, 'done' => false];
        }

        yield (object) ['value' => null, 'key' => null, 'done' => true];
    }

    /**
     * Retourne les données au format JSON
     *
     * @param int $option
     * @return string
     */
    public function toJson($option = 0)
    {
        return json_encode($this->storage, $option);
    }

    /**
     * length, longeur de la collection
     *
     * @return int
     */
    public function length()
    {
        return count($this->storage);
    }

    /**
     * Supprime le premier élément de la collection
     *
     * @return mixed
     */
    public function shift()
    {
        $data = $this->storage;
        return array_shift($data);
    }

    /**
     * Supprime le dernier élément de la collection
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->storage);
    }

    /**
     * Retourne les éléments de la collection sous format de table;
     *
     * @return array
     */
    public function toArray()
    {
        $collection = [];

        $this->recursive($this->storage, function($value, $key) use (& $collection) {
            if (is_object($value)) {
                $collection[$key] = (array) $value;
            } else {
                $collection[$key] = $value;
            }
        });

        return $collection;
    }

    /**
     * Retourne les éléments de la collection
     *
     * @return mixed
     */
    public function all()
    {
        return $this->storage;
    }

    /**
     * Ajout après le dernier élément de la collection
     *
     * @param mixed $value
     * @param int|string $key
     * @return mixed
     */
    public function push($value, $key = null)
    {
        if ($key == null) {
            $this->storage[] = $value;
        } else {
            $this->storage[$key] = $value;
        }

        return $this;
    }

    /**
     * Parcour recursive d'un tableau ou objet
     *
     * @param array $data
     * @param callable $cb
     */
    private function recursive(array $data, Callable $cb) {
        foreach($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $this->recursive((array) $value, $cb);
            } else {
                $cb($value, $key);
            }
        }
    }

    /**
     * __get
     *
     * @param string $name La clé
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * __set
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->storage[$name] = $value;
    }

    /**
     * __isset
     *
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * __unset
     *
     * @param $name
     */
    public function __unset($name)
    {
        $this->delete($name);
    }

    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * jsonSerialize
     */
    public function jsonSerialize()
    {
        return $this->storage;
    }

    /**
     * getIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->storage);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->storage[$offset]);
    }
}