<?php
/**
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */

namespace Bow\Support;

class Collection
{
	/**
	 * @var array
	 */
	private $storage;

	public function __construct()
	{
        if (func_num_args() === 1) {

            if (is_array(func_get_arg(0))) {
                $this->storage = func_get_arg(0);
            } else if (is_object(func_get_arg(0))) {
                $this->storage = (array) func_get_arg(0);
            } else {
        		$this->storage = func_get_args();
            }

        } else {
            $this->storage = func_get_args();
        }
	}

	/**
     * has, vérifie l'existance une clé dans la colléction de session
     *
     * @param string $key
     * @param bool $val Quand $val est a true alors :has vas vérifie $key non pas comment une cle mais un valeur.
     * @return boolean
     */
    public function has($key, $val = false)
    {
        // Quand $val est a true alors :has vas vérifie
        // $key non pas comment une cle mais un valeur.
        if ($val === true) {
        	return isset(array_flip($this->storage)[$key]);
        }

        return isset($this->storage[$key]);
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
     *
     * @return mixed
     */
    public function get($key = null)
    {

    	if ($this->has($key)) {
        	return $this->storage[$key];
        } else {
            if ($key !== null) {
                return null;
            }
        }

    	return $this->storage;
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

        return $r;
    }

    /**
     * collectionify, permet de récupérer une valeur ou la colléction de valeur sous forme
     * d'instance de collection.
     *
     * @param string $key=null
     *
     * @return Collection
     */
    public function collectionify($key = null)
    {

        if ($this->has($key)) {
            return new Collection($this->storage[$key]);
        } else {
            if ($key !== null) {
                return null;
            }
        }

        return new Collection($this->storage);
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
     * remove, supprime une entrée dans la colléction
     *
     * @param string $key
     * 
     * @return Collection
     */
    public function remove($key)
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
    public function each($cb)
    {
    	foreach ($this->storage as $key => $value) {
        	if (false === call_user_func_array($cb, [$value, $key])) {
                break;
            }
        }

        return $this;
    }

    /**
     * fusion la collection avec un tableau ou une autre collection
     * @param Collection|array $array
     * @throws \ErrorException
     * @return Collection
     */
    public function merge($array) {

        if (is_array($array)) {
            $this->storage = array_merge($this->storage, $array);
        } else if ($array instanceof Collection) {
            $this->storage = array_merge($this->storage, $array->get());
        } else {
            throw new \ErrorException(__METHOD__ . "(), must be take 1 parameter to be array or Collection, " . gettype($array) . " given", E_ERROR);
        }

        return $this;
    }
    /**
     * map
     *
     * @param callable $cb
     * @return Collection
     */
    public function map($cb)
    {
    	foreach ($this->storage as $key => $value) {
    		$this->storage[$key] = call_user_func_array($cb, [$value, $key]);
        }

        return $this;
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

    	return $r;
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

    	for($i = 0; $i < $offset; $i++) {
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

    	foreach ($this->storage as $key => $value) {
    		$next = call_user_func_array($cb, [$next, $value, $key, $this->storage]);
    	}

        return $this;
    }

    /**
     * count
     * 
     * @return int
     */
    public function count()
    {
    	return count($this->storage);
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

    	foreach ($this->storage as $key => $value) {
        	$sum += $value;
        }

        if ($cb !== null) {
            call_user_func_array($cb, [$sum]);
        }

    	return $sum;
    }

    /**
     * reverse
     * 
     * @return array
     */
    public function reverse()
    {
        $r = [];
        
        for($i = $this->count(); $i > 0; --$i) {
            $r[] = $this->storage[$i];
        }

        return $r;
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
            yield ["value" => $value, "key" => $key];
        }
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
}