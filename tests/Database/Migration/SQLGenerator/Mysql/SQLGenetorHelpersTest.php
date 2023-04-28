<?php

namespace Bow\Tests\Database\Migration\SQLGenerator\Mysql;

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
        $this->generator = new SQLGenerator('bow_tests', 'mysql', 'create');
    }

    /**
     * Test Add column action
     */
    public function test_add_string_sql_statement()
    {
        $sql = $this->generator->addString('name')->make();
        $this->assertNotEquals($sql, '`name` STRING NOT NULL');
        $this->assertEquals($sql, '`name` VARCHAR(255) NOT NULL');

        $sql = $this->generator->addString('name', ['default' => 'bow', 'size' => 100])->make();
        $this->assertEquals($sql, "`name` VARCHAR(100) NOT NULL DEFAULT 'bow'");

        $sql = $this->generator->addString('name', ['default' => 'bow', 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "`name` VARCHAR(100) NULL DEFAULT 'bow'");

        $sql = $this->generator->addString('name', ['primary' => true])->make();
        $this->assertEquals($sql, "`name` VARCHAR(255) PRIMARY KEY NOT NULL");

        $sql = $this->generator->addString('name', ['primary' => true, 'default' => 'bow', 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "`name` VARCHAR(100) PRIMARY KEY NULL DEFAULT 'bow'");

        $sql = $this->generator->addString('name', ['unique' => true])->make();
        $this->assertEquals($sql, "`name` VARCHAR(255) UNIQUE NOT NULL");
    }

    /**
     * Test Add column action
     * @dataProvider getNumberTypes
     */
    public function test_add_int_sql_statement(string $type, string $method, int|string $default = 1)
    {
        $type = strtoupper($type);

        $sql = $this->generator->{"add$method"}('column')->make();
        $this->assertEquals($sql, "`column` {$type} NOT NULL");

        $sql = $this->generator->{"add$method"}('column', ['default' => $default, 'size' => 100])->make();
        $this->assertEquals($sql, "`column` {$type}(100) NOT NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('column', ['default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "`column` {$type}(100) NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('column', ['primary' => true])->make();
        $this->assertEquals($sql, "`column` {$type} PRIMARY KEY NOT NULL");

        $sql = $this->generator->{"add$method"}('column', ['primary' => true, 'default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "`column` {$type}(100) PRIMARY KEY NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('column', ['unique' => true])->make();
        $this->assertEquals($sql, "`column` {$type} UNIQUE NOT NULL");

        $method = "add{$method}Increment";
        if (method_exists($this->generator, $method)) {
            $sql = $this->generator->{$method}('column')->make();
            $this->assertEquals($sql, "`column` {$type} AUTO_INCREMENT PRIMARY KEY NOT NULL");
        }
    }

    public function getNumberTypes()
    {
        return [
            ["int", "Integer", 1],
            ["bigint", "BigInteger", 1],
            ["tinyint", "TinyInteger", 1],
            ["float", "Float", 1],
            ["double", "Double", 1],
            ["smallint", "SmallInteger", 1],
            ["mediumint", "MediumInteger", 1],
        ];
    }
}
