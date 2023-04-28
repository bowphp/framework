<?php

namespace Bow\Tests\Database\Migration\Pgsql;

use Bow\Database\Exception\SQLGeneratorException;
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
        $this->generator = new SQLGenerator('bow_tests', 'pgsql', 'create');
    }

    /**
     * @dataProvider getStringTypesWithSize
     */
    public function test_add_string_sql_statement(string $type, string $method, int|string $default = 'bow')
    {
        $type = strtoupper($type);

        $sql = $this->generator->{"add$method"}('name')->make();
        $this->assertNotEquals($sql, 'name STRING NOT NULL');
        $this->assertEquals($sql, "name {$type}(255) NOT NULL");

        $sql = $this->generator->{"add$method"}('name', ['default' => $default, 'size' => 100])->make();
        $this->assertEquals($sql, "name {$type}(100) NOT NULL DEFAULT '$default'");

        $sql = $this->generator->{"add$method"}('name', ['default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "name {$type}(100) NULL DEFAULT '$default'");

        $sql = $this->generator->{"add$method"}('name', ['primary' => true])->make();
        $this->assertEquals($sql, "name {$type}(255) PRIMARY KEY NOT NULL");

        $sql = $this->generator->{"add$method"}('name', ['primary' => true, 'default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "name {$type}(100) PRIMARY KEY NULL DEFAULT '$default'");

        $sql = $this->generator->{"add$method"}('name', ['unique' => true])->make();
        $this->assertEquals($sql, "name {$type}(255) UNIQUE NOT NULL");
    }

    /**
     * @dataProvider getStringTypesWithSize
     */
    public function test_change_string_sql_statement(string $type, string $method, int|string $default = 'bow')
    {
        $type = strtoupper($type);

        $sql = $this->generator->{"change$method"}('name')->make();
        $this->assertEquals($sql, "MODIFY COLUMN name {$type}(255) NOT NULL");

        $sql = $this->generator->{"change$method"}('name', ['default' => $default, 'size' => 100])->make();
        $this->assertEquals($sql, "MODIFY COLUMN name {$type}(100) NOT NULL DEFAULT '$default'");

        $sql = $this->generator->{"change$method"}('name', ['default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN name {$type}(100) NULL DEFAULT '$default'");

        $sql = $this->generator->{"change$method"}('name', ['primary' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN name {$type}(255) PRIMARY KEY NOT NULL");

        $sql = $this->generator->{"change$method"}('name', ['primary' => true, 'default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN name {$type}(100) PRIMARY KEY NULL DEFAULT '$default'");

        $sql = $this->generator->{"change$method"}('name', ['unique' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN name {$type}(255) UNIQUE NOT NULL");
    }

    /**
     * @dataProvider getStringTypesWithoutSize
     */
    public function test_add_string_without_size_sql_statement(string $type, string $method, int|string $default = 'bow')
    {
        $type = strtoupper($type);

        $sql = $this->generator->{"add$method"}('name')->make();
        $this->assertNotEquals($sql, 'name STRING NOT NULL');
        $this->assertEquals($sql, "name {$type} NOT NULL");

        $sql = $this->generator->{"add$method"}('name', ['default' => $default, 'size' => 100])->make();
        $this->assertEquals($sql, "name {$type} NOT NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('name', ['default' => $default, 'size' => 100])->make();
        $this->assertEquals($sql, "name {$type} NOT NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('name', ['default' => $default, 'nullable' => true])->make();
        $this->assertEquals($sql, "name {$type} NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('name', ['primary' => true])->make();
        $this->assertEquals($sql, "name {$type} PRIMARY KEY NOT NULL");

        $sql = $this->generator->{"add$method"}('name', ['primary' => true, 'default' => $default, 'nullable' => true])->make();
        $this->assertEquals($sql, "name {$type} PRIMARY KEY NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('name', ['unique' => true])->make();
        $this->assertEquals($sql, "name {$type} UNIQUE NOT NULL");
    }

    /**
     * @dataProvider getStringTypesWithoutSize
     */
    public function test_change_string_without_size_sql_statement(string $type, string $method, int|string $default = 'bow')
    {
        $type = strtoupper($type);

        $sql = $this->generator->{"change$method"}('name')->make();
        $this->assertEquals($sql, "MODIFY COLUMN name {$type} NOT NULL");

        $sql = $this->generator->{"change$method"}('name', ['default' => $default, 'size' => 100])->make();
        $this->assertEquals($sql, "MODIFY COLUMN name {$type} NOT NULL DEFAULT $default");

        $sql = $this->generator->{"change$method"}('name', ['default' => $default])->make();
        $this->assertEquals($sql, "MODIFY COLUMN name {$type} NOT NULL DEFAULT $default");

        $sql = $this->generator->{"change$method"}('name', ['default' => $default, 'nullable' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN name {$type} NULL DEFAULT $default");

        $sql = $this->generator->{"change$method"}('name', ['primary' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN name {$type} PRIMARY KEY NOT NULL");

        $sql = $this->generator->{"change$method"}('name', ['primary' => true, 'default' => $default, 'nullable' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN name {$type} PRIMARY KEY NULL DEFAULT $default");

        $sql = $this->generator->{"change$method"}('name', ['unique' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN name {$type} UNIQUE NOT NULL");
    }

    /**
     * @dataProvider getNumberTypes
     */
    public function test_add_int_sql_statement(string $type, string $method, int|string $default = 1)
    {
        $type = strtoupper($type);

        $sql = $this->generator->{"add$method"}('column')->make();
        $this->assertEquals($sql, "column {$type} NOT NULL");

        $sql = $this->generator->{"add$method"}('column', ['default' => $default, 'size' => 100])->make();
        $this->assertEquals($sql, "column {$type} NOT NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('column', ['default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "column {$type} NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('column', ['primary' => true])->make();
        $this->assertEquals($sql, "column {$type} PRIMARY KEY NOT NULL");

        $sql = $this->generator->{"add$method"}('column', ['primary' => true, 'default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "column {$type} PRIMARY KEY NULL DEFAULT $default");

        $sql = $this->generator->{"add$method"}('column', ['primary' => true, 'increment' => true, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "column {$type} SERIAL PRIMARY KEY NULL");

        $sql = $this->generator->{"add$method"}('column', ['unique' => true])->make();
        $this->assertEquals($sql, "column {$type} UNIQUE NOT NULL");

        $method = "add{$method}Increment";
        if (method_exists($this->generator, $method)) {
            $sql = $this->generator->{$method}('column')->make();
            $this->assertEquals($sql, "column {$type} SERIAL PRIMARY KEY NOT NULL");
        }
    }

    /**
     * @dataProvider getNumberTypes
     */
    public function test_change_int_sql_statement(string $type, string $method, int|string $default = 1)
    {
        $type = strtoupper($type);

        $sql = $this->generator->{"change$method"}('column')->make();
        $this->assertEquals($sql, "MODIFY COLUMN column {$type} NOT NULL");

        $sql = $this->generator->{"change$method"}('column', ['default' => $default, 'size' => 100])->make();
        $this->assertEquals($sql, "MODIFY COLUMN column {$type} NOT NULL DEFAULT $default");

        $sql = $this->generator->{"change$method"}('column', ['default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN column {$type} NULL DEFAULT $default");

        $sql = $this->generator->{"change$method"}('column', ['primary' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN column {$type} PRIMARY KEY NOT NULL");

        $sql = $this->generator->{"change$method"}('column', ['primary' => true, 'default' => $default, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN column {$type} PRIMARY KEY NULL DEFAULT $default");

        $sql = $this->generator->{"change$method"}('column', ['primary' => true, 'increment' => true, 'size' => 100, 'nullable' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN column {$type} SERIAL PRIMARY KEY NULL");

        $sql = $this->generator->{"change$method"}('column', ['unique' => true])->make();
        $this->assertEquals($sql, "MODIFY COLUMN column {$type} UNIQUE NOT NULL");

        $method = "change{$method}Increment";
        if (method_exists($this->generator, $method)) {
            $sql = $this->generator->{$method}('column')->make();
            $this->assertEquals($sql, "MODIFY COLUMN column {$type} SERIAL PRIMARY KEY NOT NULL");
        }
    }

    public function test_uuid_statement()
    {
        $sql = $this->generator->addUuid('column', ['unique' => true])->make();
        $this->assertEquals($sql, "column UUID UNIQUE NOT NULL DEFAULT uuid_generate_v4()");

        $sql = $this->generator->addUuid('column', ['primary' => true])->make();
        $this->assertEquals($sql, "column UUID PRIMARY KEY NOT NULL DEFAULT uuid_generate_v4()");

        $this->expectException(SQLGeneratorException::class);
        $this->expectExceptionMessage("Cannot define the increment for uuid. You can use addUuidPrimary() instead");
        $sql = $this->generator->addUuid('column', ['primary' => true, "increment" => true])->make();
    }

    public function test_uuid_primary_statement()
    {
        $sql = $this->generator->addUuidPrimary('column')->make();
        $this->assertEquals($sql, "column UUID PRIMARY KEY NOT NULL DEFAULT uuid_generate_v4()");
    }

    public function test_uuid_should_throw_errors_with_increment_attribute()
    {
        $this->expectException(SQLGeneratorException::class);
        $this->expectExceptionMessage("Cannot define the increment for uuid.");
        $this->generator->addUuidPrimary('column', ["increment" => true])->make();
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
            ["long varchar", "LongString", "bow"],
            ["text", "Text", "bow"],
        ];
    }

    public function getStringTypesWithoutSize()
    {
        return [
            ["longtext", "Longtext", "bow"],
            ["character", "Char", "'b'"],
            ["blob", "Blob", "bow"],
            ["json", "Json", "{}"],
        ];
    }
}
