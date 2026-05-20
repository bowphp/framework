<?php

namespace Bow\Tests\Container\Stubs;

class SimpleService
{
    /**
     * @var string
     */
    private string $name;

    /**
     * SimpleService constructor
     *
     * @param string $name
     */
    public function __construct(string $name = 'default')
    {
        $this->name = $name;
    }

    /**
     * Get the service name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
