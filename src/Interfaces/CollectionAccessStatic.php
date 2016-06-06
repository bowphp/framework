<?php
namespace Bow\Interfaces;

/**
 * Interface CollectionAccessStatic
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Interfaces
 */
interface CollectionAccessStatic
{
    /**
     * has, vérifie l'existance une clé dans la colléction de session
     *
     * @param string $key
     * @return boolean
     */
    public static function has($key);

    /**
     * isEmpty, vérifie si une colléction est vide.
     *
     * @return boolean
     */
    public static function isEmpty();

    /**
     * get, permet de récupérer une valeur ou la colléction de valeur.
     *
     * @param string $key
     * @param mixed  $default [optinal]
     * @return mixed
     */
    public static function get($key, $default = null);

    /**
     * add, ajoute une entrée dans la colléction
     *
     * @param string $key
     * @param $data
     * @param bool $next
     * @return self
     */
    public static function add($key, $data, $next = false);


    /**
     * remove, supprime une entrée dans la colléction
     *
     * @param string $key
     * @return self
     */
    public static function remove($key);

    /**
     * set, modifie une entrée dans la colléction
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public static function set($key, $value);

    /**
     * retourne tout les entrées de la colléction
     * @return array
     */
    public static function toArray();

}
