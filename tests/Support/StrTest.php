<?php

namespace Bow\Tests\Support;

use Bow\Support\Str;

class StrTest extends \PHPUnit\Framework\TestCase
{
    public function test_upper()
    {
        $this->assertEquals(Str::upper('papac'), 'PAPAC');
    }

    public function test_lower()
    {
        $this->assertEquals(Str::lower('PAPAC'), 'papac');
    }

    public function test_len()
    {
        $this->assertEquals(Str::len('PAPAC'), 5);
    }

    public function test_is_lower()
    {
        $this->assertTrue(Str::isLower('papac'));
    }

    public function test_is_upper()
    {
        $this->assertTrue(Str::isUpper('PAPAC'));
    }

    public function test_is_numeric()
    {
        $this->assertTrue(Str::isNumeric('10'));
    }

    public function test_is_domain()
    {
        $this->assertTrue(Str::isDomain('https://www.github.com'));
        $this->assertFalse(Str::isDomain('httpsgithub.'));
    }

    public function test_is_alpha()
    {
        $this->assertEquals(Str::isAlpha('12340papac'), false);
    }

    public function test_is_alpha_numeric()
    {
        $this->assertTrue(Str::isAlphaNum('12340papac'));
    }

    public function test_is_email()
    {
        $this->assertTrue(Str::isMail('john@doe.com'));
        $this->assertFalse(Str::isMail('john@doe'));
    }

    public function test_is_slug()
    {
        $this->assertTrue(Str::isSlug('comment-faire-un-site-web-avec-php'));
    }

    public function test_to_slug()
    {
        $this->assertEquals(Str::slugify('comment faire un site web avec php'), 'comment-faire-un-site-web-avec-php');
    }

    public function test_to_camel()
    {
        $this->assertEquals(Str::camel('comment faire un site web avec php'), 'commentFaireUnSiteWebAvecPhp');
    }

    public function test_to_capitatize()
    {
        $this->assertEquals(Str::capitalize('comment faire un site web avec php'), 'Comment Faire Un Site Web Avec Php');
    }

    public function test_random()
    {
        $this->assertEquals(strlen(Str::random(10)), 10);
    }

    public function test_get_words()
    {
        $this->assertEquals(Str::getWords('comment faire un site web avce php', 2), 'comment faire');
    }

    public function test_contains()
    {
        $this->assertEquals(Str::contains('comment', 'comment faire un site web avce php'), 0);
    }
}
