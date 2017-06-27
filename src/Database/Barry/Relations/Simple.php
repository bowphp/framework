<?php

namespace Bow\Database\Barry\Relations;

interface Simple
{


    /**
     * Definir la clé étranger
     *
     * @param string $id
     * @return self
     */
    public function foreign($id);

    /**
     * Join avec une autre table
     *
     * @param string $table
     * @param mixed $foreign_key
     * @return self
     */
    public function merge($table, $foreign_key = null);
}