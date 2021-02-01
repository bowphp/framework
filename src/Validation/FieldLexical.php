<?php

namespace Bow\Validation;

trait FieldLexical
{
    /**
     * Get error debugging information
     *
     * @param string       $key
     * @param string|array $attributes
     *
     * @return mixed
     */
    private function lexical($key, $attribute)
    {
        if (is_string($attribute) && isset($this->messages[$attribute])) {
            return $this->messages[$attribute][$key] ?? $this->messages[$attribute];
        }

        if (is_string($attribute)) {
            $attribute = ['attribute' => $attribute];
        }

        // Get lexical provider by application part
        $lexical = trans('validation.'.$key, $attribute);

        // Get the stub lexical
        if (is_null($lexical)) {
            $lexical = $this->lexical[$key];

            foreach ($attribute as $key => $value) {
                $lexical = str_replace('{'.$key.'}', $value, $lexical);
            }
        }

        return $lexical;
    }
}
