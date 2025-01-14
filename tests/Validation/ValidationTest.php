<?php

namespace Bow\Tests\Validation;

use Bow\Database\Database;
use Bow\Translate\Translator;
use Bow\Validation\Validator;
use Bow\Tests\Config\TestingConfiguration;

class ValidationTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = TestingConfiguration::getConfig();
        Database::configure($config["database"]);
        Translator::configure($config['translate.lang'], $config["translate.dictionary"]);

        Database::statement("create table if not exists pets (id int primary key, name varchar(225));");
        Database::table("pets")->truncate();
        Database::insert("insert into pets values(1, 'Milou'), (2, 'Milou');");
    }

    public function test_in_rule()
    {
        $first_validation = Validator::make(['name' => 'papac'], ['name' => 'required|in:bow,framework']);
        $second_validation = Validator::make(['name' => 'bow'], ['name' => 'required|in:bow,framework']);

        $this->assertTrue($first_validation->fails());
        $this->assertFalse($second_validation->fails());
    }

    public function test_int_rule()
    {
        $first_validation = Validator::make(['name' => 1], ['name' => 'required|int']);
        $second_validation = Validator::make(['name' => 'bow'], ['name' => 'required|int']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($second_validation->fails());
    }

    public function test_same_rule()
    {
        $first_validation = Validator::make(['name' => 1], ['name' => 'required|same:1']);
        $second_validation = Validator::make(['name' => 'bow'], ['name' => 'required|same:framework']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($second_validation->fails());
    }

    public function test_max_rule()
    {
        $first_validation = Validator::make(['name' => 'bow'], ['name' => 'required|max:3']);
        $second_validation = Validator::make(['name' => 'framework'], ['name' => 'required|max:5']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($second_validation->fails());
    }

    public function test_min_rule()
    {
        $first_validation = Validator::make(['name' => 'bow'], ['name' => 'required|min:3']);
        $second_validation = Validator::make(['name' => 'fr'], ['name' => 'required|min:5']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($second_validation->fails());
    }

    public function test_lower_rule()
    {
        $first_validation = Validator::make(['name' => 'bow'], ['name' => 'required|lower']);
        $second_validation = Validator::make(['name' => 'BOW'], ['name' => 'required|lower']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($second_validation->fails());
    }

    public function test_upper_rule()
    {
        $first_validation = Validator::make(['name' => 'BOW'], ['name' => 'required|upper']);
        $second_validation = Validator::make(['name' => 'bow'], ['name' => 'required|upper']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($second_validation->fails());
    }

    public function test_size_rule()
    {
        $first_validation = Validator::make(['name' => 'bow'], ['name' => 'required|size:3']);
        $second_validation = Validator::make(['name' => 'framework'], ['name' => 'required|size:3']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($second_validation->fails());
    }

    public function test_alpha_rule()
    {
        $first_validation = Validator::make(['name' => 'bow'], ['name' => 'required|alpha']);
        $second_validation = Validator::make(['name' => 'bow@0.2'], ['name' => 'required|alpha']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($second_validation->fails());
    }

    public function test_alpha_num()
    {
        $first_validation = Validator::make(['name' => 'bow02'], ['name' => 'required|alphanum']);
        $second_validation = Validator::make(['name' => 'bow!223'], ['name' => 'required|alphanum']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($second_validation->fails());
    }

    public function test_number_rule()
    {
        $first_validation = Validator::make(['price' => 1], ['price' => 'required|number']);
        $second_validation = Validator::make(['price' => 'bow'], ['price' => 'required|number']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($second_validation->fails());
    }

    public function test_email_rule()
    {
        $first_validation = Validator::make(['email' => 'dakiafranck@gmail.com'], ['email' => 'required|email']);
        $second_validation = Validator::make(['email' => 'bow'], ['email' => 'required|email']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($second_validation->fails());
    }

    public function test_exists_rule()
    {
        $first_validation = Validator::make(['name' => 'Bow'], ['name' => 'required|exists:pets,name']);
        $second_validation = Validator::make(['name' => 'Milou'], ['name' => 'required|exists:pets']);

        $this->assertTrue($first_validation->fails());
        $this->assertFalse($second_validation->fails());
    }

    public function test_not_exists_rule()
    {
        $first_validation = Validator::make(['name' => 'Milou'], ['name' => 'required|!exists:pets,name']);
        $second_validation = Validator::make(['name' => 'Couli'], ['name' => 'required|!exists:pets']);

        $this->assertTrue($first_validation->fails());
        $this->assertFalse($second_validation->fails());
    }

    public function test_unique_rule()
    {
        Database::insert("insert into pets values(3, 'Couli');");

        $first_validation = Validator::make(['name' => 'Couli'], ['name' => 'required|unique:pets,name']);
        $second_validation = Validator::make(['name' => 'Milou'], ['name' => 'required|unique:pets']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($second_validation->fails());

        Database::insert("insert into pets values(4, 'Couli');");

        $thrid_validation = Validator::make(['name' => 'Couli'], ['name' => 'required|unique:pets,name']);
        $this->assertTrue($thrid_validation->fails());
    }

    public function test_required_rule()
    {
        $first_validation = Validator::make(['name' => 'Couli'], ['lastname' => 'required']);
        $second_validation = Validator::make(['name' => 'Milou'], ['name' => 'required']);

        $this->assertTrue($first_validation->fails());
        $this->assertFalse($second_validation->fails());
    }

    public function test_required_if_rule()
    {
        $first_validation = Validator::make(['name' => 'Couli'], ['lastname' => 'required_if:username']);
        $second_validation = Validator::make(['name' => 'Milou'], ['lastname' => 'required_if:name']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($second_validation->fails());
    }
}
