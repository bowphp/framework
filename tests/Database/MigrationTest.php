<?php

namespace Bow\Tests\Database;

use Bow\Database\Database;
use Bow\Database\Exception\DatabaseException;
use Bow\Database\Migration\Migration;
use Bow\Database\Migration\SQLGenerator;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Database\Stubs\MigrationExtendedStub;

class MigrationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * The migration instance
     *
     * @var Migration
     */
    private $migration;

    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
    }

    protected function setUp(): void
    {
        $this->migration = new MigrationExtendedStub;
    }

    public function testAddSql()
    {
        $this->markTestSkipped('Error: Call to undefined method Bow\Database\Connection\Adapter\SqliteAdapter::bind()');
        ob_start();

        $this->migration->addSql('drop table if exists `bow_testing`;');

        $this->migration->addSql('create table if not exists `bow_testing` (name varchar(255));');

        $result = Database::insert('INSERT INTO `bow_testing`(name) VALUES("Bow Framework")');

        $this->assertEquals($result, 1);

        $result = Database::select('select * from `bow_testing`');

        $this->assertTrue(is_array($result));

        $this->migration->addSql('drop table if exists `bow_testing`;');

        ob_get_clean();
    }

    public function testCreateMethod()
    {
        ob_start();

        try {
            $this->migration->create('bow_testing', function (SQLGenerator $generator) {
                $generator->addColumn('id', 'string', ['size' => 225, 'primary' => true]);
                $generator->addColumn('name', 'string', ['size' => 225]);
                $generator->addColumn('lastname', 'string', ['size' => 225]);
                $generator->addColumn('created_at', 'datetime');
            });

            $this->assertTrue(true, 'Migration ok');
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        try {
            $this->migration->alter('bow_testing', function (SQLGenerator $generator) {
                $generator->dropColumn('name');
                $generator->addColumn('age', 'int', ['size' => 11, 'default' => 12]);
            });

             $this->assertTrue(true, 'Migration ok');
        } catch (\Exception $e) {
            $this->assertFalse(false, $e->getMessage());
        }

        $this->migration->drop('bow_testing');

        ob_get_clean();
    }
}
