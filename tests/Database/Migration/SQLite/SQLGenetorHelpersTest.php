<?php

namespace Bow\Tests\Database\Migration\SQLite;

use Bow\Database\Migration\SQLGenerator;

class SQLGenetorHelpersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * The sql generator
     *
     * @var SQLGenerator
     */
    private $generator;

    protected function setUp(): void
    {
        $this->generator = new SQLGenerator('bow_tests', 'sqlite', 'create');
    }

    /**
     * Test Add column action
     */
    public function test_add_string_sql_statement()
    {
        $sql = $this->generator->addString('name')->make();
        $this->assertNotEquals($sql, '`name` STRING NOT NULL');
        $this->assertEquals($sql, '`name` TEXT NOT NULL');

        $sql = $this->generator->addString('name', ['default' => 'bow', 'size' => 100])->make();
        $this->assertEquals($sql, "`name` TEXT NOT NULL DEFAULT 'bow'");

        $sql = $this->generator->addString('name', ['default' => 'bow', 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "`name` TEXT NULL DEFAULT 'bow'");

        $sql = $this->generator->addString('name', ['primary' => true])->make();
        $this->assertEquals($sql, "`name` TEXT PRIMARY KEY NOT NULL");

        $sql = $this->generator->addString('name', ['primary' => true, 'default' => 'bow', 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "`name` TEXT PRIMARY KEY NULL DEFAULT 'bow'");

        $sql = $this->generator->addString('name', ['unique' => true])->make();
        $this->assertEquals($sql, "`name` TEXT UNIQUE NOT NULL");
    }

    /**
     * Test Add column action
     * @dataProvider getNumberTypes
     */
    public function test_add_int_sql_statement(string $method, int|string $default = 1)
    {
        $sql = $this->generator->{"add$method"}('column')->make();
        $this->assertEquals($sql, "`column` INTEGER NOT NULL");

        $sql = $this->generator->{"add$method"}('column', ['default' => $default, 'size' => 100])->make();
        $this->assertEquals($sql, "`column` INTEGER NOT NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('column', ['default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "`column` INTEGER NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('column', ['primary' => true])->make();
        $this->assertEquals($sql, "`column` INTEGER PRIMARY KEY NOT NULL");

        $sql = $this->generator->{"add$method"}('column', ['primary' => true, 'default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "`column` INTEGER PRIMARY KEY NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('column', ['primary' => true, 'increment' => true, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "`column` INTEGER AUTOINCREMENT PRIMARY KEY NULL");

        $sql = $this->generator->{"add$method"}('column', ['unique' => true])->make();
        $this->assertEquals($sql, "`column` INTEGER UNIQUE NOT NULL");

        $method = "add{$method}Increment";
        if (method_exists($this->generator, $method)) {
            $sql = $this->generator->{$method}('column')->make();
            $this->assertEquals($sql, "`column` INTEGER AUTOINCREMENT PRIMARY KEY NOT NULL");
        }
    }

    public function getNumberTypes()
    {
        return [
            ["Integer", 1],
            ["BigInteger", 1],
            ["TinyInteger", 1],
            ["Float", 1],
            ["Double", 1],
            ["SmallInteger", 1],
            ["MediumInteger", 1],
        ];
    }
}
