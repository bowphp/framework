<?php

namespace Bow\Database\Barry\Relations;

trait SimpleRelaion
{
    /**
     * Definir la clé étranger
     * 
     * @param  string $table
     * @param  string $id
     * @return self
     */
    public function foreign($table, $foreign_key = null)
    {
        // TODO: implement foreign method
    }

    /**
     * Join avec une autre table
     *
     * @param  string $table
     * @param  mixed  $foreign_key
     * @return self
     */
    public function merge($table, $foreign_key = null)
    {
        // TODO: implement merge method
    }
}
