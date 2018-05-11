<?php

use \Bow\Validation\Validator;

class ValidationTest extends \PHPUnit\Framework\TestCase
{
    public function testIn()
    {
        $v = Validator::make(['name' => 'papac'], ['name' => 'required|in:bow,framework']);
        $v2 = Validator::make(['name' => 'bow'], ['name' => 'required|in:bow,framework']);
        $this->assertTrue($v->fails());
        $this->assertFalse($v2->fails());
    }

    public function testInt()
    {
        $v = Validator::make(['name' => 1], ['name' => 'required|int']);
        $v2 = Validator::make(['name' => 'bow'], ['name' => 'required|int']);
        $this->assertFalse($v->fails());
        $this->assertTrue($v2->fails());
    }

    public function testSame()
    {
        $v = Validator::make(['name' => 1], ['name' => 'required|same:1']);
        $v2 = Validator::make(['name' => 'bow'], ['name' => 'required|same:framework']);
        $this->assertFalse($v->fails());
        $this->assertTrue($v2->fails());
    }

    public function testMax()
    {
        $v = Validator::make(['name' => 'bow'], ['name' => 'required|max:3']);
        $v2 = Validator::make(['name' => 'framework'], ['name' => 'required|max:5']);
        $this->assertFalse($v->fails());
        $this->assertTrue($v2->fails());
    }

    public function testLower()
    {
        $v = Validator::make(['name' => 'bow'], ['name' => 'required|lower']);
        $v2 = Validator::make(['name' => 'BOW'], ['name' => 'required|lower']);
        $this->assertFalse($v->fails());
        $this->assertTrue($v2->fails());
    }

    public function testUpper()
    {
        $v = Validator::make(['name' => 'BOW'], ['name' => 'required|upper']);
        $v2 = Validator::make(['name' => 'bow'], ['name' => 'required|upper']);
        $this->assertFalse($v->fails());
        $this->assertTrue($v2->fails());
    }

    public function testSize()
    {
        $v = Validator::make(['name' => 'bow'], ['name' => 'required|size:3']);
        $v2 = Validator::make(['name' => 'framework'], ['name' => 'required|size:3']);
        $this->assertFalse($v->fails());
        $this->assertTrue($v2->fails());
    }

    public function testAlpha()
    {
        $v = Validator::make(['name' => 'bow'], ['name' => 'required|alpha']);
        $v2 = Validator::make(['name' => 'bow@0.2'], ['name' => 'required|alpha']);
        $this->assertFalse($v->fails());
        $this->assertTrue($v2->fails());
    }

    public function testAlphaNum()
    {
        $v = Validator::make(['name' => 'bow02'], ['name' => 'required|alphanum']);
        $v2 = Validator::make(['name' => 'bow!223'], ['name' => 'required|alphanum']);
        $this->assertFalse($v->fails());
        $this->assertTrue($v2->fails());
    }

    public function testNumber()
    {
        $v = Validator::make(['price' => 1], ['price' => 'required|number']);
        $v2 = Validator::make(['price' => 'bow'], ['price' => 'required|number']);
        $this->assertFalse($v->fails());
        $this->assertTrue($v2->fails());
    }

    public function testEmail()
    {
        $v = Validator::make(['email' => 'dakiafranck@gmail.com'], ['email' => 'required|email']);
        $v2 = Validator::make(['email' => 'bow'], ['email' => 'required|email']);
        $this->assertFalse($v->fails());
        $this->assertTrue($v2->fails());
    }

    public function testExists()
    {
        $v = Validator::make(['name' => 'Couli'], ['name' => 'required|exists:pets,name']);
        $v2 = Validator::make(['name' => 'bow'], ['name' => 'required|exists:pets']);

        $this->assertFalse($v->fails());
        $this->assertTrue($v2->fails());
    }
}