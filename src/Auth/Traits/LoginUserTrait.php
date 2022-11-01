<?php

declare(strict_types=1);

namespace Bow\Auth\Traits;

trait LoginUserTrait
{
    /**
     * Make login
     *
     * @param array $credentials
     * @return \Bow\Database\Barry\Model|null
     */
    private function makeLogin(array $credentials)
    {
        $model = $this->provider['model'];
        $fields = $this->provider['credentials'];

        return $model::where($fields['username'], $credentials[$fields['username']])->first();
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
