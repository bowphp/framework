<?php
namespace Bow\Support;

class Arraydotify implements \ArrayAccess
{
    /**
     * @var array
     */
    private $array = [];

    /**
     * @var array
     */
    private $origin = [];

    /**
     * DotifyArray constructor.
     * @param array $array
     */
    public function __construct(array $array = [])
    {
        $this->origin = $array;
        $this->array = $this->convertToDot($array);
    }

    /**
     * @param array $array
     * @return Arraydotify
     */
    public static function make(array $array = [])
    {
        return new Arraydotify($array);
    }

    /**
     * @param array $array
     * @param string $prepend
     * @return array
     */
    private function convertToDot(array $array, $prepend = '')
    {
        $dot = [];

        foreach ($array as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = (array) $value;
                $dot = array_merge($dot, $this->convertToDot(
                    $value, $prepend.$key.'.'
                ));
                continue;
            }

            $dot[$prepend.$key] = $value;
        }

        return $dot;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        $depth = explode('.', $offset);

        if (count($depth) == 1) {
            return isset($this->origin[$offset]);
        }

        return isset($this->array[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        if (! $this->offsetExists($offset)) {
            return null;
        }

        return isset($this->array[$offset]) ? $this->array[$offset] : $this->origin[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        return $this->array[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->array[$offset]);
    }
}