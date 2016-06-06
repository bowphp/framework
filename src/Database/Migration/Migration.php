<?php
namespace Bow\Database\Migration;

abstract class Migration
{
    /**
     * @return mixed
     */
    abstract public function up();

    /**
     * @return mixed
     */
    abstract public function down();

    /**
     * Remplir une table
     */
    public function fill()
    {
        Schema::fillTable(static::$table, static::$marsk);
    }
}