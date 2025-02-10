<?php

namespace Bow\Tests\Support;

use Bow\Support\Arraydotify;

class ArraydotifyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Bow\Support\Arraydotify
     */
    protected Arraydotify $dot;

    /**
     * @var array
     */
    protected array $collection = [
        'name' => 'bow',
        'lastname' => 'framework',
        'bio' => 'The php micro framework',
        'author' => [
            'name' => 'Franck Dakia',
            'email' => 'dakiafranck@gmail.com'
        ],
        'location' => [
            'city' => 'Abidjan',
            'tel' => "12346678",
            "state" => [
                'code' => 225,
                'abr' => 'CI',
                'name' => 'Ivoiry Cost'
            ]
        ]
    ];

    public function test_get_normal()
    {
        $this->assertTrue(is_array($this->dot['code']));
    }

    public function test_get_code_name()
    {
        $this->assertEquals($this->dot['code.name'], 'bow');
    }

    public function test_get_code_lastname()
    {
        $this->assertEquals($this->dot['code.lastname'], 'framework');
    }

    public function test_get_code_location()
    {
        $this->assertEquals($this->dot['code.location.state.abr'], 'CI');
    }

    public function test_get_location()
    {
        $this->assertTrue(is_array($this->dot['code.location']));
    }

    public function test_get_locationContaines()
    {
        $this->assertArrayHasKey('city', $this->dot['code.location']);
        $this->assertArrayHasKey('tel', $this->dot['code.location']);
        $this->assertArrayHasKey('state', $this->dot['code.location']);
        $this->assertTrue(is_array($this->dot['code.location.state']));
        $this->assertArrayHasKey('code', $this->dot['code.location.state']);
    }

    public function test_get_unset_location()
    {
        unset($this->dot['code.location']);

        $this->assertTrue(isset($this->dot['code.location']));
    }

    protected function setUp(): void
    {
        $this->dot = new \Bow\Support\Arraydotify(['code' => $this->collection]);
    }
}
