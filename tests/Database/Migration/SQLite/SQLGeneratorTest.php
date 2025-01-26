<?php

namespace Bow\Tests\Database\Migration\SQLite;

use Bow\Database\Database;
use Bow\Database\Migration\SQLGenerator;
use Bow\Tests\Config\TestingConfiguration;

class SQLGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * The sql generator
     *
     * @var SQLGenerator
     */
    private $generator;

    /**
     * Test Add column action
     */
    public function test_add_column_sql_statement()
    {
        $this->generator->addColumn('name', 'int');
        $sql = $this->generator->make();
        $this->assertEquals($sql, '`name` INTEGER NOT NULL');

        $this->generator->addColumn('name', 'string');
        $sql = $this->generator->make();
        $this->assertNotEquals($sql, '`name` STRING NOT NULL');

        $this->generator->addColumn('name', 'string');
        $sql = $this->generator->make();
        $this->assertEquals($sql, '`name` TEXT NOT NULL');

        $this->generator->addColumn('name', 'string', ['default' => 'bow', 'size' => 100]);
        $sql = $this->generator->make();
        $this->assertEquals($sql, "`name` TEXT NOT NULL DEFAULT 'bow'");
    }

    /**
     * Test Change column action
     */
    public function test_change_column_sql_statement()
    {
        $this->generator->changeColumn('name', 'int');
        $sql = $this->generator->make();
        $this->assertEquals($sql, 'MODIFY COLUMN `name` INTEGER NOT NULL');

        $this->generator->changeColumn('name', 'string');
        $sql = $this->generator->make();
        $this->assertNotEquals($sql, 'MODIFY COLUMN `name` STRING NOT NULL');

        $this->generator->changeColumn('name', 'string');
        $sql = $this->generator->make();
        $this->assertEquals($sql, 'MODIFY COLUMN `name` TEXT NOT NULL');

        $this->generator->changeColumn('name', 'string', ['default' => 'bow', 'size' => 100]);
        $sql = $this->generator->make();
        $this->assertEquals($sql, "MODIFY COLUMN `name` TEXT NOT NULL DEFAULT 'bow'");
    }

    /**
     * Test Rename column action
     */
    public function test_add_column_with_after_sql_statement()
    {
        $this->generator->addColumn('lastname', 'string', ['after' => 'firstname']);
        $sql = $this->generator->make();

        $this->assertNotEquals($sql, '`lastname` TEXT NOT NULL AFTER `firstname` FIRST');
        $this->assertEquals($sql, '`lastname` TEXT NOT NULL');
    }

    /**
     * Test Rename column action
     */
    public function test_change_column_with_after_sql_statement()
    {
        $this->generator->changeColumn('lastname', 'string', ['after' => 'firstname']);
        $sql = $this->generator->make();

        $this->assertNotEquals($sql, 'MODIFY COLUMN `lastname` TEXT NOT NULL AFTER `firstname`');
        $this->assertEquals($sql, 'MODIFY COLUMN `lastname` TEXT NOT NULL');
    }

    /**
     * Test Rename column action
     */
    public function test_change_column_with_first_sql_statement()
    {
        $this->generator->changeColumn('lastname', 'string');
        $sql = $this->generator->make();

        $this->assertEquals($sql, 'MODIFY COLUMN `lastname` TEXT NOT NULL');
    }

    /**
     * Test Add column action
     */
    public function test_should_create_drop_column_sql_statement_as_empty()
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
        Database::connection("sqlite");

        $this->generator->dropColumn('name');
        $sql = $this->generator->make();

        $this->assertEquals($sql, '');
    }

    /**
     * Test Add column action
     */
    public function test_should_create_drop_column_sql_statement()
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
        Database::connection("sqlite");

        Database::statement('DROP TABLE IF EXISTS pets; CREATE TABLE pets(id integer primary key autoincrement, name text not null, age integer not null)');
        Database::insert("insert into pets(name, age) values('Milou', 2)");
        Database::insert("insert into pets(name, age) values('Jor', 1)");

        $this->generator->setTable('pets');
        $this->generator->dropColumn('name');
        $pet = Database::selectOne("select * from pets");

        $this->assertFalse(isset($pet->name));
    }

    public function test_should_create_correct_datetime_sql_statement()
    {
        $this->generator->addDatetime('created_at', ['default' => 'CURRENT_TIMESTAMP']);
        $sql = $this->generator->make();

        $this->assertEquals($sql, '`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function test_should_create_not_correct_datetime_sql_statement()
    {
        $this->generator->addDatetime('created_at');
        $sql = $this->generator->make();

        $this->assertNotEquals($sql, '`created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function test_should_create_correct_timestamps_sql_statement()
    {
        $this->generator->addTimestamps();
        $sql = $this->generator->make();

        $this->assertEquals($sql, '`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    protected function setUp(): void
    {
        $this->generator = new SQLGenerator('bow_tests', 'sqlite', 'create');
    }
}
