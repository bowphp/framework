<?php

namespace Bow\Tests\Http;

use Bow\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_SERVER['CONTENT_TYPE'], $_SERVER['HTTP_CONTENT_TYPE']);
    }

    /**
     * Input is data, never code: is_callable() is true for the name of any
     * defined function, so resolving it would let a client call `phpinfo` (or
     * any other zero-argument function) simply by submitting its name, and
     * would hand the caller that function's return value instead of the string
     * it asked for.
     *
     * @dataProvider callableLookingInput
     */
    public function testInputNamingAFunctionIsNotInvoked(string $value): void
    {
        $this->assertTrue(is_callable($value), "fixture {$value} must be a real function");

        $_POST = ['field' => $value];

        $request = new Request();
        $request->capture();

        $this->assertSame($value, $request->get('field'));
    }

    /** A callable default is the feature this guard must preserve. */
    public function testCallableDefaultIsStillResolved(): void
    {
        $request = new Request();
        $request->capture();

        $this->assertSame('resolved', $request->get('absent', fn () => 'resolved'));
        $this->assertSame('plain', $request->get('absent', 'plain'));
    }

    /** @return array<string, array{0: string}> */
    public static function callableLookingInput(): array
    {
        return [
            'php builtin'      => ['phpinfo'],
            'string function'  => ['trim'],
            'array function'   => ['compact'],
        ];
    }
}
