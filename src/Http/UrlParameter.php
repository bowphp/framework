<?php

namespace Bow\Http;

class UrlParameter implements \ArrayAccess
{
    /**
     * @var array
     */
    private $param = [];

    /**
     * UrlParameter constructor.
     *
     * @param array $param
     */
    public function __construct(array $param)
    {
        $this->param = $param;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return isset($this->param[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        return $this->param[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        throw new \Exception('Impossible de modifier les paramètres.');
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        throw new \Exception('Impossible de modifier les paramètres.');
    }

    /**
     * @inheritDoc
     */
    function __get($name)
    {
        return $this->offsetExists($name);
    }
}