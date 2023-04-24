<?php

namespace Bow\Tests\Database\Migration;

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
        $this->generator = new SQLGenerator('bow_tests');
    }

    /**
     * Test Add column action
     */
    public function test_add_string_sql_statement()
    {
        $generator = new SQLGenerator('bow_tests');
        $generator->addString('name');
        $sql = $generator->make();
        $this->assertNotEquals($sql, '`name` STRING NOT NULL');
        $this->assertEquals($sql, '`name` VARCHAR(255) NOT NULL');

        $generator = new SQLGenerator('bow_tests');
        $generator->addString('name', ['default' => 'bow', 'size' => 100]);
        $sql = $generator->make();
        $this->assertEquals($sql, "`name` VARCHAR(100) NOT NULL DEFAULT 'bow'");

        $generator = new SQLGenerator('bow_tests');
        $generator->addString('name', ['default' => 'bow', 'size' => 100, 'nullable' => true]);
        $sql = $generator->make();
        $this->assertEquals($sql, "`name` VARCHAR(100) NULL DEFAULT 'bow'");

        $generator = new SQLGenerator('bow_tests');
        $generator->addString('name', ['primary' => true]);
        $sql = $generator->make();
        $this->assertEquals($sql, "`name` VARCHAR(100) NOT NULL PRIMARY");

        $generator = new SQLGenerator('bow_tests');
        $generator->addString('name', ['unique' => true]);
        $sql = $generator->make();
        $this->assertEquals($sql, "`name` VARCHAR(100) NOT NULL UNIQUE");
    }
}
