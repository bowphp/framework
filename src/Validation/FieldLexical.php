<?php

declare(strict_types=1);

namespace Bow\Validation;

trait FieldLexical
{
    /**
     * Get error debugging information
     *
     * @param string       $key
     * @param string|array $value
     * @return ?string
     */
    private function lexical(string $key, string|array $value): ?string
    {
        $data = array_merge(
            $this->inputs ?? [],
            is_array($value) ? $value : ['attribute' => $value]
        );

        if (is_array($value) && isset($value['attribute'])) {
            $message = $this->messages[$value['attribute']][$key] ?? $this->messages[$value['attribute']] ?? null;

            if (is_string($message)) {
                return $this->parseAttribute($data, $message);
            }

            if (is_null($message)) {
                return $this->parseFromTranslate($key, $data);
            }
        }

        if (is_string($value) && isset($this->messages[$value])) {
            $message = $this->messages[$value][$key] ?? $this->messages[$value];

            if (is_string($message)) {
                return $this->parseAttribute($data, $message);
            }
        }

        return $this->parseFromTranslate($key, $data);
    }

    /**
     * Parse the translate content
     *
     * @param string $key
     * @param array $data
     * @return string
     */
    private function parseFromTranslate(string $key, array $data)
    {
        // Get lexical provided by dev app
        $message = trans('validation.' . $key, $data);

        if (is_null($message)) {
            $message = $this->lexical[$key];
        }

        return $this->parseAttribute($data, $message);
    }

    /**
     * Normalize beneficiaries
     *
     * @param array $attribute
     * @param string $lexical
     * @return string
     */
    private function parseAttribute(array $attribute, string $lexical): ?string
    {
        foreach ($attribute as $key => $value) {
            $lexical = str_replace('{' . $key . '}', (string) $value, $lexical);
        }

        return $lexical;
    }
}
