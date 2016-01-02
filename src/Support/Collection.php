<?php


namespace Snoop\Support;


class Collection
{
	/**
	 * @var array
	 */
	private $data;

	public function __construct()
	{
		$this->data = func_get_args();
	}

	/**
     * has, vérifie l'existance une clé dans la colléction de session
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
    	return isset($this->data[$key]);
    }

    /**
     * isEmpty, vérifie si une colléction est vide.
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
     * @param string $key=null
     * 
     * @return mixed
     */
    public function get($key = null)
    {

    	if ($this->has($key)) {
	    
        	return $this->data[$key];
    	
        }

    	return $this->data;
    }

    /**
     * add, ajoute une entrée dans la colléction
     *
     * @param string $key
     * @param $data
     * @param bool $next
     * 
     * @return $this
     */
    public function add($key, $data = null, $next = false)
    {
        if ($data !== null) {
        	
            $this->data[$key] = $data;
            
        } else {

            $data = $key;
            $this->data[] = $data; 

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

    	unset($this->data[$key]);
    
        return $this;
    
    }

    /**
     * set, modifie une entrée dans la colléction
     *
     * @param string $key
     * @param mixed $value
     * 
     * @return mixed
     */
    public function set($key, $value)
    {
    	if ($this->has($key)) {
    		
            $old = $this->data[$key];
    		$this->data[$key] = $value;

    		return $old;
    	}
    	
    	$this->data[$key] = $value;
    	
        return null;
    }

    /**
     * each
     * 
     * @param callable $cb
     * 
     * @return $this
     */
    public function each($cb)
    {
    	foreach ($this->data as $key => $value) {
    	
        	call_user_func_array($cb, [$key, $value]);
    	
        }

        return $this;
    }

    /**
     * map
     * 
     * @return $this
     */
    public function map($cb)
    {
    	foreach ($this->data as $key => $value) {

    		$this->data[$key] = call_user_func_array($cb, [$key, $value]);
    	
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

    	foreach ($this->data as $key => $value) {
    		
            if (call_user_func_array($cb, [$key, $value])) {
    		
            	$r[] = $this->data[$key];
    		
            }

    	}

    	return $r;
    }

    /**
     * Fill
     * 
     * @param mixed $num
     * @param int $offset
     * 
     * @return array
     */
    public function fill($num, $offset)
    {
    	$r = [];

    	for($i = 0; $i < $offset; $i++) {
    	
        	$this->data[$i] = $num;
    	
        }
    	
        return $r;
    }

    /**
     * reduce
     * 
     * @param callable $cb
     * 
     * @return $this
     */
    public function reduce($cb)
    {

    	$i = 0;
    	$next = null;

    	foreach ($this->data as $key => $value) {
    		
    		if ($i === 0) {
    	
        		$next = call_user_func_array($cb, [$next, $value, $key, $this->data]);
    	
        	} else {
    	
        		$next = call_user_func_array($cb, [$next, $value, $key, $this->data]);
    	
        	}

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
    	return count($this->data);
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

    	foreach ($this->data as $key => $value) {
    	
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

            $r[] = $this->data[$i];

        }

        return $r;

    }

}