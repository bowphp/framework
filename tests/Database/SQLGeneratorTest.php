<?php

namespace Bow\Tests\Database;

use Bow\Database\Migration\SQLGenerator;

class SQLGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * The sql generator
     *
     * @var SQLGenerator
     */
    private $generator;

    protected function setUp(): void
    {
        $this->generator = new SQLGenerator('bow_tests');
    }

    /**
     * Test Add column action
     */
    public function test_add_column_sql_statement()
    {
        $this->generator->addColumn('name', 'int');
        $sql = $this->generator->make();
        $this->assertEquals($sql, '`name` INT NOT NULL');

        $this->generator->addColumn('name', 'string');
        $sql = $this->generator->make();
        $this->assertNotEquals($sql, '`name` STRING NOT NULL');

        $this->generator->addColumn('name', 'string');
        $sql = $this->generator->make();
        $this->assertEquals($sql, '`name` VARCHAR(255) NOT NULL');

        $this->generator->addColumn('name', 'string', ['default' => 'bow', 'size' => 100]);
        $sql = $this->generator->make();
        $this->assertEquals($sql, "`name` VARCHAR(100) NOT NULL DEFAULT 'bow'");
    }

    /**
     * Test Add column action
     */
    public function test_should_create_drop_column_sql_statement()
    {
        $this->generator->dropColumn('name');
        $sql = $this->generator->make();
        $this->assertEquals($sql, 'DROP COLUMN `name`');

        $this->generator->dropColumn('name');
        $sql = $this->generator->make();
        $this->assertEquals($sql, 'DROP COLUMN `name`');
    }

    public function test_should_create_correct_datetime_sql_statement()
    {
        $this->generator->setAdapter('sqlite');
        $this->generator->addDatetime('created_at', ['default' => 'CURRENT_TIMESTAMP']);

        $sql = $this->generator->make();

        $this->assertEquals($sql, '`created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function test_should_create_not_correct_datetime_sql_statement()
    {
        $this->generator->setAdapter('sqlite');

        $this->generator->addDatetime('created_at');

        $sql = $this->generator->make();

        $this->assertNotEquals($sql, '`created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function test_should_create_correct_timestamps_sql_statement()
    {
        $this->generator->setAdapter('sqlite');

        $this->generator->addTimestamps();

        $sql = $this->generator->make();

        $this->assertEquals($sql, '`created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }
}