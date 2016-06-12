<?php
namespace Bow\Database\Migration;

abstract class Migration
{
    /**
     * Le nom de la table.
     *
     * @var string
     */
    protected static $table;

    /**
     * Le marsque
     *
     * @var array
     */
    protected static $marks;

    /**
     * Crér ou Met à jour une table
     */
    abstract public function up();

    /**
     * Supprimer ou Met à jour une table
     */
    abstract public function down();

    /**
     * Remplir une table
     * @param int $n
     */
    public function fill($n = 1)
    {
        Schema::fillTable(static::$table, static::$marks, $n);
    }
}