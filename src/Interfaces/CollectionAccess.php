<?php
namespace Bow\Interfaces;

/**
 * Interface CollectionAccess
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Interfaces
 */
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
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default);

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

    /**
     * retourne tout les entrées de la colléction
     * @return array
     */
    public function toArray();

    /**
     * retourne tout les entrées de la colléction sous forme d'object
     * @return array
     */
    public function toObject();

}
