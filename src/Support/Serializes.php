<?php

namespace Bow\Support;

use ReflectionClass;
use ReflectionProperty;

trait Serializes
{
    /**
     * Prepare the instance values for serialization.
     *
     * @return array
     */
    public function __serialize()
    {
        $values = [];

        $properties = (new ReflectionClass($this))->getProperties();

        $class = get_class($this);

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if (!$property->isInitialized($this)) {
                continue;
            }

            $value = $this->getPropertyValue($property);

            if ($property->hasDefaultValue() && $value === $property->getDefaultValue()) {
                continue;
            }

            $name = $property->getName();

            if ($property->isPrivate()) {
                $name = "\0{$class}\0{$name}";
            } elseif ($property->isProtected()) {
                $name = "\0*\0{$name}";
            }

            $values[$name] = $value;
        }

        return $values;
    }

    /**
     * Get the property value for the given property.
     *
     * @param ReflectionProperty $property
     * @return mixed
     */
    protected function getPropertyValue(
        ReflectionProperty $property
    ): mixed
    {
        return $property->getValue($this);
    }

    /**
     * Restore the model after serialization.
     *
     * @param array $values
     * @return void
     */
    public function __unserialize(array $values): void
    {
        $properties = (new ReflectionClass($this))->getProperties();

        $class = get_class($this);

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();

            if ($property->isPrivate()) {
                $name = "\0{$class}\0{$name}";
            } elseif ($property->isProtected()) {
                $name = "\0*\0{$name}";
            }

            if (!array_key_exists($name, $values)) {
                continue;
            }

            $property->setValue(
                $this,
                $values[$name]
            );
        }
    }
}
