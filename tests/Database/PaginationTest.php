<?php

namespace Bow\Tests\Database;

use Bow\Database\Database;
use Bow\Tests\Config\TestingConfiguration;

class PaginationTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
        Database::statement('create table if not exists pets (id int primary key, name varchar(255))');
        Database::table("pets")->truncate();
        foreach (range(1, 30) as $key) {
            Database::insert('insert into pets values(:id, :name)', ['id' => $key, 'name' => 'Pet ' . $key]);
        }
    }

    public function test_go_current_pagination()
    {
        $result = Database::table("pets")->paginate(10);

        $this->assertIsArray($result);
        $this->assertEquals(count($result["data"]), 10);
        $this->assertEquals($result["per_page"], 10);
        $this->assertEquals($result["total"], 3);
        $this->assertEquals($result["current"], 1);
        $this->assertEquals($result["previous"], 1);
        $this->assertEquals($result["next"], 2);
    }

    public function test_go_next_2_pagination()
    {
        $result = Database::table("pets")->paginate(10, 2);

        $this->assertIsArray($result);
        $this->assertEquals(count($result["data"]), 10);
        $this->assertEquals($result["per_page"], 10);
        $this->assertEquals($result["total"], 3);
        $this->assertEquals($result["current"], 2);
        $this->assertEquals($result["previous"], 1);
        $this->assertEquals($result["next"], 3);
    }

    public function test_go_next_3_pagination()
    {
        $result = Database::table("pets")->paginate(10, 3);

        $this->assertIsArray($result);
        $this->assertEquals(count($result["data"]), 10);
        $this->assertEquals($result["per_page"], 10);
        $this->assertEquals($result["total"], 3);
        $this->assertEquals($result["current"], 3);
        $this->assertEquals($result["previous"], 2);
        $this->assertEquals($result["next"], false);
    }
}
