<?php

namespace Bow\Tests\Database\Migration\Pgsql;

use Bow\Database\Exception\SQLGeneratorException;
use Bow\Database\Migration\SQLGenerator;

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
        $this->assertEquals($sql, '"name" INT NOT NULL');

        $this->generator->addColumn('name', 'string');
        $sql = $this->generator->make();
        $this->assertNotEquals($sql, '"name" STRING NOT NULL');

        $this->generator->addColumn('name', 'string');
        $sql = $this->generator->make();
        $this->assertEquals($sql, '"name" VARCHAR(255) NOT NULL');

        $this->generator->addColumn('name', 'string', ['default' => 'bow', 'size' => 100]);
        $sql = $this->generator->make();
        $this->assertEquals($sql, '"name" VARCHAR(100) NOT NULL DEFAULT \'bow\'');
    }

    /**
     * Test Change column action
     */
    public function test_change_column_sql_statement()
    {
        $this->generator->changeColumn('name', 'int');
        $sql = $this->generator->make();
        $this->assertEquals($sql, 'MODIFY COLUMN "name" INT NOT NULL');

        $this->generator->changeColumn('name', 'string');
        $sql = $this->generator->make();
        $this->assertNotEquals($sql, 'MODIFY COLUMN "name" STRING NOT NULL');

        $this->generator->changeColumn('name', 'string');
        $sql = $this->generator->make();
        $this->assertEquals($sql, 'MODIFY COLUMN "name" VARCHAR(255) NOT NULL');

        $this->generator->changeColumn('name', 'string', ['default' => 'bow', 'size' => 100]);
        $sql = $this->generator->make();
        $this->assertEquals($sql, 'MODIFY COLUMN "name" VARCHAR(100) NOT NULL DEFAULT \'bow\'');
    }

    /**
     * Test Rename column action
     */
    public function test_rename_column_sql_statement_for_mysql()
    {
        $this->generator->renameColumn('name', 'fullname');
        $sql = $this->generator->make();
        $this->assertEquals($sql, 'RENAME COLUMN "name" TO fullname');
    }

    /**
     * Test Rename column action
     */
    public function test_add_column_with_after_sql_statement()
    {
        $this->expectException(SQLGeneratorException::class);
        $this->expectExceptionMessage("The key first and after only work on mysql");

        $this->generator->addColumn('lastname', 'string', ['after' => 'firstname']);
        $this->generator->make();
    }

    /**
     * Test Rename column action
     */
    public function test_change_column_with_after_sql_statement()
    {
        $this->expectException(SQLGeneratorException::class);
        $this->expectExceptionMessage("The key first and after only work on mysql");

        $this->generator->changeColumn('lastname', 'string', ['after' => 'firstname']);
        $this->generator->make();
    }

    /**
     * Test Rename column action
     */
    public function test_change_column_with_first_sql_statement()
    {
        $this->expectException(SQLGeneratorException::class);
        $this->expectExceptionMessage("The key first and after only work on mysql");

        $this->generator->changeColumn('lastname', 'string', ['first' => true]);
        $this->generator->make();
    }

    /**
     * Test Add column action
     */
    public function test_should_create_drop_column_sql_statement()
    {
        $this->generator->dropColumn('name');
        $sql = $this->generator->make();
        $this->assertEquals($sql, 'DROP COLUMN name');

        $this->generator->dropColumn('name');
        $sql = $this->generator->make();
        $this->assertEquals($sql, 'DROP COLUMN name');
    }

    public function test_should_create_correct_datetime_sql_statement()
    {
        $this->generator->addDatetime('created_at', ['default' => 'CURRENT_TIMESTAMP']);
        $sql = $this->generator->make();

        $this->assertEquals($sql, '"created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function test_should_create_not_correct_datetime_sql_statement()
    {
        $this->generator->addDatetime('created_at');
        $sql = $this->generator->make();

        $this->assertNotEquals($sql, '"created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function test_should_create_correct_timestamps_sql_statement()
    {
        $this->generator->addTimestamps();
        $sql = $this->generator->make();

        $this->assertEquals($sql, '"created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    protected function setUp(): void
    {
        $this->generator = new SQLGenerator('bow_tests', 'pgsql', 'create');
    }
}
