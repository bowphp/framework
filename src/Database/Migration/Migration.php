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
}