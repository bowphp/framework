<?php

declare(strict_types=1);

namespace Bow\Auth\Traits;

use Bow\Database\Barry\Model;
use Bow\Auth\Exception\AuthenticationException;

trait LoginUserTrait
{
    /**
     * Make login
     *
     * @param array $credentials
     * @return ?Model
     */
    private function makeLogin(array $credentials): ?Model
    {
        $model = $this->provider['model'];
        $fields = $this->provider['credentials'];

        if (!isset($credentials[$fields['username']])) {
            throw new AuthenticationException(
                "Please check your passed variable for make attemps login."
                ."The 'credentials.{$fields['username']}' key not found."
            );
        }

        if (!isset($credentials[$fields['password']])) {
            throw new AuthenticationException(
                "Please check your passed variable for make attemps login."
                ."The 'credentials.{$fields['password']}' key not found."
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
     * @param string $value
     * @return \Bow\Database\Barry\Model|null
     */
    private function getUserBy($key, $value)
    {
        $model = $this->provider['model'];

        return $model::where($key, $value)->first();
    }
}
