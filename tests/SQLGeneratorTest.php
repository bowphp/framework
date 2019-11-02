<?php

use Bow\Database\Migration\SQLGenerator;

class SQLGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * The sql generator
     *
     * @var SQLGenerator
     */
    private $generator;

    public function setUp()
    {
        $this->generator = new SQLGenerator('bow_tests');
    }
    
    /**
     * Test Add column action
     */
    public function testAddColumn()
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
    public function testDropColumn()
    {
        $this->generator->dropColumn('name');
        $sql = $this->generator->make();
        $this->assertEquals($sql, 'DROP COLUMN `name`');

        $this->generator->dropColumn('name');
        $sql = $this->generator->make();
        $this->assertEquals($sql, 'DROP COLUMN `name`');
    }
}
