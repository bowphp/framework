<?php declare(strict_types=1);

namespace Bow\Validation\Rules;

use Bow\Database\Database;

trait DatabaseRule
{
    /**
     * Compile Exists Rule
     *
     * [exists:table,column] Check that the contents of a table field exist
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileExists(string $key, string $masque): void
    {
        if (!preg_match("/^exists:(.+)$/", $masque, $match)) {
            return;
        }

        $catch = end($match);
        $parts = explode(',', $catch);

        if (count($parts) == 1) {
            $exists = Database::table($parts[0])
                ->where($key, $this->inputs[$key])->exists();
        } else {
            $exists = Database::table($parts[0])
                ->where($parts[1], $this->inputs[$key])->exists();
        }

        if (!$exists) {
            $this->last_message = $this->lexical('exists', $key);

            $this->fails = true;

            $this->errors[$key][] = [
                "masque" => $masque,
                "message" => $this->last_message
            ];
        }
    }

    /**
     * Compile Not Exists Rule
     *
     * [!exists:table,column] Checks that the contents of the field of a table do not exist
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileNotExists(string $key, string $masque): void
    {
        if (!preg_match("/^!exists:(.+)$/", $masque, $match)) {
            return;
        }

        $catch = end($match);
        $parts = explode(',', $catch);

        if (count($parts) == 1) {
            $exists = Database::table($parts[0])
                ->where($key, $this->inputs[$key])->exists();
        } else {
            $exists = Database::table($parts[0])
                ->where($parts[1], $this->inputs[$key])->exists();
        }

        if ($exists) {
            $this->last_message = $this->lexical('not_exists', $key);

            $this->fails = true;

            $this->errors[$key][] = [
                "masque" => $masque,
                "message" => $this->last_message
            ];
        }
    }

    /**
     * Compile Unique Rule
     *
     * [unique:table,column] Check that the contents of the field of a table is a single value
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileUnique(string $key, string $masque): void
    {
        if (!preg_match("/^unique:(.+)$/", $masque, $match)) {
            return;
        }

        $catch = end($match);
        $parts = explode(',', $catch);

        if (count($parts) == 1) {
            $count = Database::table($parts[0])
                ->where($key, $this->inputs[$key])->count();
        } else {
            $count = Database::table($parts[0])
                ->where($parts[1], $this->inputs[$key])->count();
        }

        if ($count > 1) {
            $this->last_message = $this->lexical('unique', $key);

            $this->fails = true;

            $this->errors[$key][] = [
                "masque" => $masque,
                "message" => $this->last_message
            ];
        }
    }
}
