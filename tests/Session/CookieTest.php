<?php

namespace Bow\Tests\Session;

use Bow\Session\Cookie;
use Bow\Tests\Config\TestingConfiguration;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CookieTest extends TestCase
{
    private static bool $configured = false;

    public static function setUpBeforeClass(): void
    {
        if (!static::$configured) {
            TestingConfiguration::getConfig();
            static::$configured = true;
        }
    }

    /**
     * Invoke the private Cookie::options() builder.
     */
    private function options(int $expiration): array
    {
        $method = new ReflectionMethod(Cookie::class, 'options');
        $method->setAccessible(true);

        return $method->invoke(null, $expiration);
    }

    /**
     * The stub session config sets domain to null (SESSION_DOMAIN unset). It
     * must reach setcookie() as a string, never null — that null is what would
     * deprecate on PHP 8.x and fatally throw on PHP 9.
     */
    public function test_null_domain_is_coerced_to_empty_string()
    {
        $options = $this->options(3600);

        $this->assertSame('', $options['domain']);
        $this->assertIsString($options['domain']);
    }

    /**
     * Every option must carry its declared scalar type so setcookie() never
     * receives a null in a non-nullable slot.
     */
    public function test_options_are_strictly_typed()
    {
        $options = $this->options(3600);

        $this->assertIsInt($options['expires']);
        $this->assertIsString($options['path']);
        $this->assertIsString($options['domain']);
        $this->assertIsBool($options['secure']);
        $this->assertIsBool($options['httponly']);
        $this->assertIsString($options['samesite']);
    }

    /**
     * Values come from the session config; SameSite falls back to Lax.
     */
    public function test_options_reflect_session_config()
    {
        $options = $this->options(3600);

        $this->assertSame('/', $options['path']);
        $this->assertFalse($options['secure']);
        // The stub config explicitly sets httponly to false.
        $this->assertFalse($options['httponly']);
        $this->assertSame('Lax', $options['samesite']);
    }

    /**
     * Cookie::remove() clears by delegating to set() with a negative lifetime.
     * The clearing cookie must therefore carry the same path/domain/samesite
     * attributes (browsers require a matching attribute set to overwrite), and
     * its expiry must land in the past.
     */
    public function test_clearing_cookie_keeps_matching_attributes_and_past_expiry()
    {
        $live = $this->options(3600);
        $clear = $this->options(-1000);

        $this->assertLessThan($live['expires'], $clear['expires']);
        $this->assertLessThan(time(), $clear['expires']);

        $this->assertSame($live['path'], $clear['path']);
        $this->assertSame($live['domain'], $clear['domain']);
        $this->assertSame($live['samesite'], $clear['samesite']);
        $this->assertSame($live['secure'], $clear['secure']);
        $this->assertSame($live['httponly'], $clear['httponly']);
    }
}
