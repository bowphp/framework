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
    private function lexical($key, $attributes)
    {
        if (is_string($attributes)) {
            $attributes = ['attribute' => $attributes];
        }

        // Get lexical provider by application part
        $lexical = trans('validation.' . $key, $attributes);

        if (is_null($lexical)) {
            $lexical = $this->lexical[$key];

            foreach ($attributes as $key => $value) {
                $lexical = str_replace(':' . $key, $value, $lexical);
            }
        }

        return $lexical;
    }
}
