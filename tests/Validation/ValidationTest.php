<?php

namespace Bow\Tests\Validation;

use Bow\Validation\Validator;

class ValidationTest extends \PHPUnit\Framework\TestCase
{
    public function test_in_rule()
    {
        $first_validation = Validator::make(['name' => 'papac'], ['name' => 'required|in:bow,framework']);
        $secand_validation = Validator::make(['name' => 'bow'], ['name' => 'required|in:bow,framework']);

        $this->assertTrue($first_validation->fails());
        $this->assertFalse($secand_validation->fails());
    }

    public function test_int_rule()
    {
        $first_validation = Validator::make(['name' => 1], ['name' => 'required|int']);
        $secand_validation = Validator::make(['name' => 'bow'], ['name' => 'required|int']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($secand_validation->fails());
    }

    public function test_same_rule()
    {
        $first_validation = Validator::make(['name' => 1], ['name' => 'required|same:1']);
        $secand_validation = Validator::make(['name' => 'bow'], ['name' => 'required|same:framework']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($secand_validation->fails());
    }

    public function test_max_rule()
    {
        $first_validation = Validator::make(['name' => 'bow'], ['name' => 'required|max:3']);
        $secand_validation = Validator::make(['name' => 'framework'], ['name' => 'required|max:5']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($secand_validation->fails());
    }

    public function test_lower_rule()
    {
        $first_validation = Validator::make(['name' => 'bow'], ['name' => 'required|lower']);
        $secand_validation = Validator::make(['name' => 'BOW'], ['name' => 'required|lower']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($secand_validation->fails());
    }

    public function test_upper_rule()
    {
        $first_validation = Validator::make(['name' => 'BOW'], ['name' => 'required|upper']);
        $secand_validation = Validator::make(['name' => 'bow'], ['name' => 'required|upper']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($secand_validation->fails());
    }

    public function test_size_rule()
    {
        $first_validation = Validator::make(['name' => 'bow'], ['name' => 'required|size:3']);
        $secand_validation = Validator::make(['name' => 'framework'], ['name' => 'required|size:3']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($secand_validation->fails());
    }

    public function test_alpha_rule()
    {
        $first_validation = Validator::make(['name' => 'bow'], ['name' => 'required|alpha']);
        $secand_validation = Validator::make(['name' => 'bow@0.2'], ['name' => 'required|alpha']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($secand_validation->fails());
    }

    public function test_alpha_num()
    {
        $first_validation = Validator::make(['name' => 'bow02'], ['name' => 'required|alphanum']);
        $secand_validation = Validator::make(['name' => 'bow!223'], ['name' => 'required|alphanum']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($secand_validation->fails());
    }

    public function test_number_rule()
    {
        $first_validation = Validator::make(['price' => 1], ['price' => 'required|number']);
        $secand_validation = Validator::make(['price' => 'bow'], ['price' => 'required|number']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($secand_validation->fails());
    }

    public function testEmail()
    {
        $first_validation = Validator::make(['email' => 'dakiafranck@gmail.com'], ['email' => 'required|email']);
        $secand_validation = Validator::make(['email' => 'bow'], ['email' => 'required|email']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($secand_validation->fails());
    }

    public function testExists()
    {
        $this->markTestSkipped(sprintf('Failed asserting that true is false on line %s.', __LINE__));

        $first_validation = Validator::make(['name' => 'Couli'], ['name' => 'required|exists:pets,name']);
        $secand_validation = Validator::make(['name' => 'bow'], ['name' => 'required|exists:pets']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($secand_validation->fails());
    }

    public function testNotExists()
    {
        $this->markTestSkipped(sprintf('Failed asserting that true is false on line %s.', __LINE__));

        $first_validation = Validator::make(['name' => 'OtherData'], ['name' => 'required|!exists:pets,name']);
        $secand_validation = Validator::make(['name' => 'Couli'], ['name' => 'required|!exists:pets']);

        $this->assertFalse($first_validation->fails());
        $this->assertTrue($secand_validation->fails());
    }

    public function testUnique()
    {
        $this->markTestSkipped(sprintf('Failed asserting that true is false on line %s.', __LINE__));

        $first_validation = Validator::make(['name' => 'Couli'], ['name' => 'required|unique:pets,name']);
        $secand_validation = Validator::make(['name' => 'Milou'], ['name' => 'required|unique:pets']);

        $this->assertTrue($first_validation->fails());
        $this->assertTrue($secand_validation->fails());
    }
}
