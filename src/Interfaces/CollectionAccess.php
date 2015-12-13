<?php

namespace System\Interfaces;

interface CollectionAccess
{
    /**
     * isKey, verifie l'existance d'un
     * cle dans la table
     * @param string $key
     * @return boolean
     */
    public function isKey($key);

    /**
     *  IsEmpty
     *	@return boolean
     */
    public function IsEmpty();

    /**
     * get, permet de manipuler le donnee
     * d'un tableau.
     * permet de recuperer d'une valeur ou
     * la collection de valeur.
     * @param string $key=null
     * @return mixed
     */
    public function get($key = null);

}
