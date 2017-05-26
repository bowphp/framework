<?php
use Bow\Support\Str;

class TestStr extends \PHPUnit\Framework\TestCase
{
    public function testUpper()
    {
        $this->assertEquals(Str::upper('papac'), 'PAPAC');
    }

    public function testLower()
    {
        $this->assertEquals(Str::lower('PAPAC'), 'papac');
    }

    public function testLen()
    {
        $this->assertEquals(Str::len('PAPAC'), 5);
    }

    public function testIsLower()
    {
        $this->assertTrue(Str::isLower('papac'));
    }

    public function testIsUpper()
    {
        $this->assertTrue(Str::isUpper('PAPAC'));
    }

    public function testIsNumeric()
    {
        $this->assertTrue(Str::isNumeric('10'));
    }

    public function testIsDomain()
    {
        $this->assertTrue(Str::isDomain('https://www.github.com'));
        $this->assertFalse(Str::isDomain('httpsgithub.'));
    }

    public function testIsAlpha()
    {
        $this->assertEquals(Str::isAlpha('12340papac'), false);
    }

    public function testIsAlphaNumeric()
    {
        $this->assertTrue(Str::isAlphaNum('12340papac'));
    }

    public function testIsEmail()
    {
        $this->assertTrue(Str::isMail('john@doe.com'));
        $this->assertFalse(Str::isMail('john@doe'));
    }

    public function testIsSlug()
    {
        $this->assertTrue(Str::isSlug('comment-faire-un-site-web-avec-php'));
    }

    public function testToSlug()
    {
        $this->assertEquals(Str::slugify('comment faire un site web avec php'), 'comment-faire-un-site-web-avec-php');
    }

    public function testToCamel()
    {
        $this->assertEquals(Str::camel('comment faire un site web avec php'), 'commentFaireUnSiteWebAvecPhp');
    }

    public function testToCapitatize()
    {
        $this->assertEquals(Str::capitalize('comment faire un site web avec php'), 'Comment Faire Un Site Web Avec Php');
    }

    public function testRandomize()
    {
        $this->assertEquals(strlen(Str::randomize(10)), 10);
    }

    public function testGetWords()
    {
        $this->assertEquals(Str::getWords('comment faire un site web avce php', 2), 'comment faire');
    }

    public function testContains()
    {
        $this->assertEquals(Str::contains('comment', 'comment faire un site web avce php'), 0);
    }
}