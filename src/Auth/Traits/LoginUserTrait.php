<?php

declare(strict_types=1);

namespace Bow\Auth\Traits;

use Bow\Auth\Authentication;
use Bow\Auth\Exception\AuthenticationException;
use Bow\Database\Barry\Model;

trait LoginUserTrait
{
    /**
     * Make login
     *
     * @param array $credentials
     * @return ?Authentication
     * @throws AuthenticationException
     */
    private function makeLogin(array $credentials): ?Authentication
    {
        $model = $this->provider['model'];
        $fields = $this->provider['credentials'];

        if (!isset($credentials[$fields['username']])) {
            throw new AuthenticationException(
                "Please check your passed variable for make attemps login."
                . "The 'credentials.{$fields['username']}' key not found."
            );
        }

        if (!isset($credentials[$fields['password']])) {
            throw new AuthenticationException(
                "Please check your passed variable for make attemps login."
                . "The 'credentials.{$fields['password']}' key not found."
            );
        }

        $column = $fields['username'];
        $value = $credentials[$fields['username']];

        return $model::where($column, $value)->first();
    }

    /**
     * Get user by key
     *
     * @param string $key
     * @param float|int|string $value
     * @return Model|null
     */
    private function getUserBy(string $key, float|int|string $value): ?Authentication
    {
        $model = $this->provider['model'];

        return $model::where($key, $value)->first();
    }
}
