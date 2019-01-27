<?php

use Bow\Database\Database;
use Bow\Database\Migration\Migration;
use Bow\Database\Exception\DatabaseException;
use Bow\Database\Migration\SQLGenerator;

class MigrationExtending extends Migration
{
    public function up()
    {
    }
    public function rollback()
    {
    }
}

class MigrationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * The migration instance
     *
     * @var Migration
     */
    private $migration;

    public function setUp()
    {
        $this->migration = new MigrationExtending;
    }

    public function testAddSql()
    {
        ob_start();

        $this->migration->addSql('DROP TABLE IF EXISTS `bow_tests`;');

        $this->migration->addSql('CREATE TABLE IF NOT EXISTS `bow_tests` (name VARCHAR(255));');

        $r = Database::insert('INSERT INTO `bow_tests`(name) VALUES("Bow Framework")');

        $this->assertEquals($r, 1);
        
        $r = Database::select('SELECT * FROM `bow_tests`');

        $this->assertTrue(is_array($r));

        $this->migration->addSql('DROP TABLE IF EXISTS `bow_tests`;');

        ob_get_clean();
    }

    public function testCreateMethod()
    {
        ob_start();

        try {
            $this->migration->create('bow_tests', function (SQLGenerator $generator) {
                $generator->addColumn('id', 'string', ['size' => 225, 'primary' => true]);
                $generator->addColumn('name', 'string', ['size' => 225]);
                $generator->addColumn('lastname', 'string', ['size' => 225]);
                $generator->addColumn('created_at', 'datetime');
            });

            $this->assertTrue(true, 'Migration ok');
        } catch (\Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        try {
            $this->migration->alter('bow_tests', function (SQLGenerator $generator) {
                $generator->dropColumn('name');
                $generator->addColumn('age', 'int', ['size' => 11, 'default' => 12]);
            });
            
             $this->assertTrue(true, 'Migration ok');
        } catch (\Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }

        $this->migration->drop('bow_tests');

        ob_get_clean();
    }
}
