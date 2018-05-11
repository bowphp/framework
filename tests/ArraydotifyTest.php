<?php

class ArraydotifyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Bow\Support\Arraydotify
     */
    protected $dot;

    /**
     * @var array
     */
    protected $collection = [
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

    public function setUp()
    {
        $this->dot = new \Bow\Support\Arraydotify(['code' => $this->collection]);
    }

    public function testGetNormal()
    {
        $this->assertTrue(is_array($this->dot['code']));
    }

    public function testGetCodeName()
    {
        $this->assertEquals($this->dot['code.name'], 'bow');
    }

    public function testGetCodeLastname()
    {
        $this->assertEquals($this->dot['code.lastname'], 'framework');
    }

    public function testGetCodeLocation()
    {
        $this->assertEquals($this->dot['code.location.state.abr'], 'CI');
    }

    public function testGetLocation()
    {
        $this->assertTrue(is_array($this->dot['code.location']));
    }

    public function testGetLocationContaines()
    {
        $this->assertArrayHasKey('city', $this->dot['code.location']);
        $this->assertArrayHasKey('tel', $this->dot['code.location']);
        $this->assertArrayHasKey('state', $this->dot['code.location']);
        $this->assertTrue(is_array($this->dot['code.location.state']));
        $this->assertArrayHasKey('code', $this->dot['code.location.state']);
    }

    public function testGetUnsetLocation()
    {
        unset($this->dot['code.location']);
        $this->assertTrue(isset($this->dot['code.location']));
    }
}