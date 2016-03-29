<?php

namespace Bow\Interfaces;

interface CollectionAccess
{
    /**
     * has, vérifie l'existance une clé dans la colléction de session
     *
     * @param string $key
     * @return boolean
     */
    public function has($key);

    /**
     * isEmpty, vérifie si une colléction est vide.
     *
     * @return boolean
     */
    public function isEmpty();

    /**
     * get, permet de récupérer une valeur ou la colléction de valeur.
     *
     * @param string $key=null
     * @param mixed $default=false
     * @return mixed
     */
    public function get($key = null, $default);

    /**
     * add, ajoute une entrée dans la colléction
     *
     * @param string $key
     * @param $data
     * @param bool $next
     * @return self
     */
    public function add($key, $data, $next = false);


    /**
     * remove, supprime une entrée dans la colléction
     *
     * @param string $key
     * @return self
     */
    public function remove($key);

    /**
     * set, modifie une entrée dans la colléction
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set($key, $value);

}
