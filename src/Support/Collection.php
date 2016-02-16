<?php


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
     * @param bool $val
     * @return boolean
     */
    public function has($key, $val = false)
    {   
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
    	return empty($this->storage);
    }

    /**
     * get, permet de récupérer une valeur ou la colléction de valeur.
     *
     * @param string $key=null
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
     * add, ajoute une entrée dans la colléction
     *
     * @param string $key
     * @param $data
     * 
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function each($cb)
    {
    	foreach ($this->storage as $key => $value) {
        	call_user_func_array($cb, [$value, $key]);
        }

        return $this;
    }

    /**
     * map
     *
     * @param callable $cb
     * @return $this
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
     * keys retourne la liste des clés de la collection
     * 
     * @return array
     */
    public function keys()
    {
        return array_keys($this->storage);
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
     * @return array
     */
    public function length()
    {
       return count($this->storage);
    }
}