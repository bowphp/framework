<?php

namespace Bow\Support;

class Arraydotify implements \ArrayAccess
{
    /**
     * The array collection
     *
     * @var array
     */
    private $array = [];

    /**
     * The origin array
     *
     * @var array
     */
    private $origin = [];

    /**
     * Arraydotify constructor.
     *
     * @param array $array
     *
     * @return void
     */
    public function __construct(array $array = [])
    {
        $this->array = $this->dotify($array);

        $this->origin = $array;
    }

    /**
     * Update the original data
     *
     * @return void
     */
    private function updateOrigin()
    {
        foreach ($this->array as $key => $value) {
            $this->dataSet($this->origin, $key, $value);
        }
    }

    /**
     * Make array dotify
     *
     * @param array $array
     *
     * @return Arraydotify
     */
    public static function make(array $array = [])
    {
        return new Arraydotify($array);
    }

    /**
     * Dotify action
     *
     * @param array  $array
     * @param string $prepend
     *
     * @return array
     */
    private function dotify(array $array, $prepend = '')
    {
        $dot = [];

        foreach ($array as $key => $value) {
            if (!(is_array($value) || is_object($value))) {
                $dot[$prepend.$key] = $value;
                continue;
            }

            $value = (array) $value;

            $dot = array_merge($dot, $this->dotify(
                $value,
                $prepend.$key.'.'
            ));
        }

        return $dot;
    }

    /**
     * Transform the dot access to array access
     *
     * @param array  $array
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    private function dataSet(&$array, $key, $value)
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
     * Find information to the origin array
     *
     * @param array $origin
     * @param string $segment
     *
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
