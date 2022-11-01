<?php

namespace Bow\Validation;

trait FieldLexical
{
    /**
     * Get error debugging information
     *
     * @param string       $key
     * @param string|array $attributes
     * @return ?string
     */
    private function lexical($key, $attributes): ?string
    {
        if (is_string($attributes) && isset($this->messages[$attributes])) {
            return $this->messages[$attributes][$key] ?? $this->messages[$attributes];
        }

        if (is_array($attributes)
            && isset($attributes['attribute'])
            && isset($this->messages[$attributes['attribute']])) {
            return $this->messages[$attributes['attribute']][$key] ?? $this->messages[$attributes['attribute']];
        }

        if (is_string($attributes)) {
            $attributes = ['attribute' => $attributes];
        }

        // Get lexical provided by dev app
        $lexical = trans('validation.' . $key, $attributes);

        if (is_null($lexical)) {
            $lexical = $this->parseAttribute($attributes, $this->lexical[$key]);
        }

        return $lexical;
    }

    /**
     * Normalize beneficiaries
     *
     * @param array $attributes
     * @param string $lexical
     * @return string
     */
    private function parseAttribute(array $attributes, string $lexical): ?string
    {
        foreach ($attributes as $key => $value) {
            $lexical = str_replace('{' . $key . '}', $value, $lexical);
        }

        return $lexical;
    }
}
