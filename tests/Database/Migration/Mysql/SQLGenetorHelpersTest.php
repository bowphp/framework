<?php

namespace Bow\Tests\Database\Migration\Mysql;

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
     * @dataProvider getStringTypesWithSize
     */
    public function test_add_string_sql_statement(string $type, string $method, int|string $default = 'bow')
    {
        $type = strtoupper($type);

        $sql = $this->generator->{"add$method"}('name')->make();
        $this->assertNotEquals($sql, 'name STRING NOT NULL');

        if (preg_match('/STRING|VARCHAR/', $type)) {
            $this->assertEquals($sql, "`name` {$type}(255) NOT NULL");
        } else {
            $this->assertEquals($sql, "`name` {$type} NOT NULL");
        }

        $sql = $this->generator->{"add$method"}('name', ['default' => $default, 'size' => 100])->make();
        $this->assertEquals($sql, "`name` {$type}(100) NOT NULL DEFAULT '$default'");

        $sql = $this->generator->{"add$method"}('name', ['default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "`name` {$type}(100) NULL DEFAULT '$default'");

        $sql = $this->generator->{"add$method"}('name', ['primary' => true])->make();
        if (preg_match('/STRING|VARCHAR/', $type)) {
            $this->assertEquals($sql, "`name` {$type}(255) PRIMARY KEY NOT NULL");
        } else {
            $this->assertEquals($sql, "`name` {$type} PRIMARY KEY NOT NULL");
        }

        $sql = $this->generator->{"add$method"}('name', ['primary' => true, 'default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "`name` {$type}(100) PRIMARY KEY NULL DEFAULT '$default'");

        $sql = $this->generator->{"add$method"}('name', ['unique' => true])->make();
        if (preg_match('/STRING|VARCHAR/', $type)) {
            $this->assertEquals($sql, "`name` {$type}(255) UNIQUE NOT NULL");
        } else {
            $this->assertEquals($sql, "`name` {$type} UNIQUE NOT NULL");
        }
    }

    /**
     * @dataProvider getStringTypesWithSize
     */
    public function test_change_string_sql_statement(string $type, string $method, int|string $default = 'bow')
    {
        $type = strtoupper($type);

        $sql = $this->generator->{"change$method"}('name')->make();

        if (preg_match('/STRING|VARCHAR/', $type)) {
            $this->assertEquals($sql, "MODIFY COLUMN `name` {$type}(255) NOT NULL");
        } else {
            $this->assertEquals($sql, "MODIFY COLUMN `name` {$type} NOT NULL");
        }

        $sql = $this->generator->{"change$method"}('name', ['default' => $default, 'size' => 100])->make();
        $this->assertEquals($sql, "MODIFY COLUMN `name` {$type}(100) NOT NULL DEFAULT '$default'");

        $sql = $this->generator->{"change$method"}('name', ['default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN `name` {$type}(100) NULL DEFAULT '$default'");

        $sql = $this->generator->{"change$method"}('name', ['primary' => true])->make();
        if (preg_match('/STRING|VARCHAR/', $type)) {
            $this->assertEquals($sql, "MODIFY COLUMN `name` {$type}(255) PRIMARY KEY NOT NULL");
        } else {
            $this->assertEquals($sql, "MODIFY COLUMN `name` {$type} PRIMARY KEY NOT NULL");
        }

        $sql = $this->generator->{"change$method"}('name', ['primary' => true, 'default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN `name` {$type}(100) PRIMARY KEY NULL DEFAULT '$default'");

        $sql = $this->generator->{"change$method"}('name', ['unique' => true])->make();
        if (preg_match('/STRING|VARCHAR/', $type)) {
            $this->assertEquals($sql, "MODIFY COLUMN `name` {$type}(255) UNIQUE NOT NULL");
        } else {
            $this->assertEquals($sql, "MODIFY COLUMN `name` {$type} UNIQUE NOT NULL");
        }
    }

    /**
     * @dataProvider getStringTypesWithoutSize
     */
    public function test_add_string_without_size_sql_statement(string $type, string $method, int|string $default = 'bow')
    {
        $type = strtoupper($type);

        $sql = $this->generator->{"add$method"}('name')->make();
        $this->assertNotEquals($sql, 'name STRING NOT NULL');
        $this->assertEquals($sql, "`name` {$type} NOT NULL");

        $sql = $this->generator->{"add$method"}('name', ['default' => $default, 'size' => 100])->make();
        $this->assertEquals($sql, "`name` {$type}(100) NOT NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('name', ['default' => $default, 'size' => 100])->make();
        $this->assertEquals($sql, "`name` {$type}(100) NOT NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('name', ['default' => $default, 'nullable' => true])->make();
        $this->assertEquals($sql, "`name` {$type} NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('name', ['primary' => true])->make();
        $this->assertEquals($sql, "`name` {$type} PRIMARY KEY NOT NULL");

        $sql = $this->generator->{"add$method"}('name', ['primary' => true, 'default' => $default, 'nullable' => true])->make();
        $this->assertEquals($sql, "`name` {$type} PRIMARY KEY NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('name', ['unique' => true])->make();
        $this->assertEquals($sql, "`name` {$type} UNIQUE NOT NULL");
    }

    /**
     * @dataProvider getStringTypesWithoutSize
     */
    public function test_change_string_without_size_sql_statement(string $type, string $method, int|string $default = 'bow')
    {
        $type = strtoupper($type);

        $sql = $this->generator->{"change$method"}('name')->make();
        $this->assertEquals($sql, "MODIFY COLUMN `name` {$type} NOT NULL");

        $sql = $this->generator->{"change$method"}('name', ['default' => $default, 'size' => 100])->make();
        $this->assertEquals($sql, "MODIFY COLUMN `name` {$type}(100) NOT NULL DEFAULT $default");

        $sql = $this->generator->{"change$method"}('name', ['default' => $default])->make();
        $this->assertEquals($sql, "MODIFY COLUMN `name` {$type} NOT NULL DEFAULT $default");

        $sql = $this->generator->{"change$method"}('name', ['default' => $default, 'nullable' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN `name` {$type} NULL DEFAULT $default");

        $sql = $this->generator->{"change$method"}('name', ['primary' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN `name` {$type} PRIMARY KEY NOT NULL");

        $sql = $this->generator->{"change$method"}('name', ['primary' => true, 'default' => $default, 'nullable' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN `name` {$type} PRIMARY KEY NULL DEFAULT $default");

        $sql = $this->generator->{"change$method"}('name', ['unique' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN `name` {$type} UNIQUE NOT NULL");
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

    public function getStringTypesWithSize()
    {
        return [
            ["varchar", "String", "bow"],
            ["text", "Text", "bow"],
            ["char", "Char", "b"],
        ];
    }

    public function getStringTypesWithoutSize()
    {
        return [
            ["longtext", "Longtext", "bow"],
            ["blob", "Blob", "bow"],
            ["json", "Json", "{}"],
        ];
    }
}
