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
     *
     * @param array $array
     */
    public function __construct(array $array = [])
    {
        $this->array = $this->dotify($array);

        $this->origin = $array;
    }

    /**
     * Permet de metre a jour les données d'origine
     */
    private function updateOrigin()
    {
        foreach ($this->array as $key => $value) {
            $this->dataSet($this->origin, $key, $value);
        }
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
     * @param array  $array
     * @param string $prepend
     * @return array
     */
    private function dotify(array $array, $prepend = '')
    {
        $dot = [];

        foreach ($array as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = (array) $value;

                $dot = array_merge($dot, $this->dotify(
                    $value,
                    $prepend.$key.'.'
                ));

                continue;
            }

            $dot[$prepend.$key] = $value;
        }

        return $dot;
    }

    /**
     * @param array  $array
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    public function dataSet(&$array, $key, $value)
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        if (isset($this->array[$offset])) {
            return true;
        }

        $array = $this->find($this->origin, $offset);

        return (is_array($array) && !empty($array));
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        return isset($this->array[$offset])
            ? $this->array[$offset]
            : $this->find($this->origin, $offset);
    }

    /**
     * @param $origin
     * @param $segment
     * @return array|mixed|null
     */
    private function find($origin, $segment)
    {
        $parts = explode('.', $segment);

        $array = [];

        foreach ($parts as $key => $part) {
            if ($key != 0) {
                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                }

                continue;
            }

            if (!isset($origin[$part])) {
                return null;
            }

            if (!is_array($origin[$part])) {
                return [$origin[$part]];
            }

            $array = &$origin[$part];
        }

        return $array;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->array[$offset] = $value;

        $this->updateOrigin();
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->array[$offset]);

        $this->updateOrigin();
    }
}
