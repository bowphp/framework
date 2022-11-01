<?php declare(strict_types=1);

namespace Bow\Validation\Rules;

trait DatetimeRule
{
    /**
     * Compile Date Rule
     *
     * [date] Check that the field's content is a valid date
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileDate(string $key, string $masque): void
    {
        if (!preg_match("/^date?$/", $masque, $match)) {
            return;
        }

        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $this->inputs[$key])) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexical('date', $key);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Date Time Rule
     *
     * [datetime] Check that the contents of the field is a valid date time
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileDateTime(string $key, string $masque): void
    {
        if (!preg_match("/^datetime$/", $masque, $match)) {
            return;
        }

        if (!preg_match(
            '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/i',
            $this->inputs[$key]
        )) {
            return;
        }

        $this->last_message = $this->lexical('datetime', $key);

        $this->fails = true;

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }
}
