<?php


namespace System\Support;

use System\Interfaces\CollectionAccess;


class Cookie implements CollectionAccess
{
	
	public static function loadCookie()
	{
        return true;
	}

    public function read()
	{
        return true;
	}

    /**
     * isKey, verifie l'existance d'un
     * cle dans la table
     * 
     * 
     * @param string $key
     * @return boolean
     */
    public function isKey($key)
    {

    }

    /**
     *  IsEmpty
     *	@return boolean
     */
    public function IsEmpty()
    {

    }

    /**
     * get, permet de manipuler le donnee
     * d'un tableau.
     * permet de recuperer d'une valeur ou
     * la collection de valeur.
     * 
     * 
     * @param string $key=null
     * @return mixed
     */
    public function get($key = null)
    {

    }

    /**
     * addSession, permet d'ajout une value
     * dans le tableau.
     * 
     * 
     * @param string|int $key
     * @param mixed $data
     * @param boolean $next=null
     * @throws \InvalidArgumentException
     */
    public function add($key, $data, $next = null)
    {

    }

    /**
     * remove, supprime un entree dans la
     * table
     * 
     * 
     * @param string $key
     * @return self
     */
    public function remove($key)
    {

    }

}