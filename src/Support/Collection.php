<?php
namespace Bow\Support;

/**
 * Classe de la manipulation de donnés dans un tableau
 *
 * @class Collection
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class Collection
{
	/**
	 * @var array
	 */
	private $storage = [];

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
            return $this->storage[$key];
        } else {
            if ($default !== null) {
                if (is_callable($default)) {
                    return call_user_func($default);
                }
            }
        }

    	return null;
    }

    /**
     * retourne la liste des valeurs de la collection
     * @return array
     */
    public function values()
    {
        $r = [];

        foreach($this->storage as $value) {
            array_push($r, $value);
        }

        return $r;
    }

    /**
     * retourne la liste des clés de la collection
     * @return array
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
     * collectionify, permet de récupérer une valeur ou la colléction de valeur sous forme
     * d'instance de collection.
     *
     * @param string $key La clé de l'élément
     *
     * @return Collection
     */
    public function collectionify($key)
    {
        if ($this->has($key)) {
            $insData = $this->storage[$key];
            if (!is_array($insData)) {
                $insData = [$insData];
            }
            return new Collection($insData);
        } else {
            return null;
        }
    }

    /**
     * add, ajoute une entrée dans la colléction
     *
     * @param string|int $key
     * @param $data
     * 
     * @return Collection
     */
    public function add($key, $data = null)
    {
        if ($data !== null) {
            $this->storage[$key] = $data;
        } else {
            $data = $key;
            $this->storage[] = $data; 
        }

        return $this;
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
        	if (call_user_func_array($cb, [$value, $key])) {
                break;
            }
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
            throw new \ErrorException(__METHOD__ . "(), must be take 1 parameter to be array or Collection, " . gettype($array) . " given", E_ERROR);
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
     * @return array
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
     * 
     * @return self
     */
    public function reduce($cb)
    {
    	$next = null;

    	foreach ($this->storage as $key => $current) {
    		$next = call_user_func_array($cb, [$next, $current, $key, $this->storage]);
    	}

        return $this;
    }

    /**
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
     * @return int
     */
    public function max($cb = null)
    {
    	return $this->side($cb, "max");
    }

    /**
     * Max
     *
     * @param callable $cb
     *
     * @return int
     */
    public function min($cb = null)
    {
    	return $this->side($cb, "min");
    }

    /**
     * side Execute max|min
     *
     * @param callable $cb
     * @param string $type
     *
     * @return int
     */
    private function side($cb = null, $type)
    {
        $data = [];

        $this->recursive($this->storage, function($value) use (& $data) {
            if (is_numeric($value)) {
                $data[] = $value;
            }
        });

        $r = call_user_func_array("$type", [$data]);

        if (is_callable($cb)) {
            call_user_func_array($cb, [$r]);
        }

    	return $r;
    }

    /**
     * Permet d'ignorer la clé que l'on lui donne
     * et retourne une instance de Collection.
     *
     * @param array $except Liste des éléments à ignorer
     * @return Collection
     */
    public function except(array $except)
    {
        $data = [];
        $this->recursive($this->storage, function($value, $key) use (& $data, $except) {
            if (!in_array($key, $except)) {
                $data[$key] = $value;
            }
        });

        return new Collection($data);
    }

    /**
     * reverse
     *
     * @param bool $collectionify
     *
     * @return array
     */
    public function reverse($collectionify = false)
    {
        $r = [];
        
        for($i = $this->length(); $i > 0; --$i) {
            $r[] = $this->storage[$i];
        }

        if ($collectionify) {
            $c = new Collection($r);
            foreach($r as $key => $value) {
                $c->add($key, $value);
            }
            $r = $c;
        }

        return new Collection($r);
    }

    /**
     * update, met à jour une valeur existant dans la collection
     *
     * @param string|integer $key
     * @param mixed $data
     * @param boolean $overide 
     */
    public function update($key, $data, $overide = false)
    {
        if ($this->has($key)) {
            if (is_array($this->storage[$key])) {
                if ($overide === true) {
                    $this->storage[$key] = $data;
                } else {
                    if (!is_array($data)) {
                        $data = [$data];
                    }
                    $this->storage[$key] = array_merge($this->storage[$key], $data);
                }
            } else {
                $this->storage[$key] = $data;
            }
        }
    }

    /**
     * yieldify, lance un générateur
     * 
     * @return array
     */
    public function yieldify()
    {
        foreach ($this->storage as $key => $value) {
            yield (object) ["value" => $value, "key" => $key, "done" => false];
        }

        yield (object) ["value" => null, "key" => null, "done" => true];
    }

    /**
     * Retourne les données au format JSON
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->storage);
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
        return array_shift($this->storage);
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
        $data = [];

        $this->recursive($this->storage, function($value, $key) use (& $data) {
            if (is_object($value)) {
                $data[$key] = (array) $value;
            } else {
                $data[$key] = $value;
            }
        });

        return $data;
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
     * @return mixed
     */
    public function push($value)
    {
        $data = func_get_args();

        array_unshift($data, $this->storage);
        call_user_func_array("array_push", $data);

        $this->storage = $data;

        return $this;
    }

    /**
     * @param array $data
     * @param callable $cb
     */
    private function recursive(array $data, Callable $cb) {
        foreach($data as $key => $value) {
            if (is_array($value)) {
                $this->recursive($data, $cb);
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
        $this->add($name, $value);
    }
}