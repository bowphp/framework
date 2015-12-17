<?php

namespace System\Interfaces;

interface CollectionAccess
{
    /**
     * isKey, verifie l'existance d'un clé dans la collection de session
     *
     * @param string $key
     * @return boolean
     */
    public function isKey($key);

    /**
     * IsEmpty, vérifie si une collection est vide.
     *
     *	@return boolean
     */
    public function IsEmpty();

    /**
     * get, permet de recuperer d'une valeur ou la collection de valeur.
     *
     * @param string $key=null
     * @return mixed
     */
    public function get($key = null);

    /**
     * add, ajouté une entrée dans la collection
     *
     * @param string $key
     * @param $data
     * @param bool $next
     * @return self
     */
    public function add($key, $data, $next = false);


    /**
     * remove, supprime une entree dans la collection
     *
     * @param string $key
     * @return self
     */
    public function remove($key);

    /**
     * set, modifier une entree dans la collection
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set($key, $value);

}
