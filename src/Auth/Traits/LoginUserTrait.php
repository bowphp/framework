<?php

namespace Bow\Auth\Traits;

trait LoginUserTrait
{
    /**
     * Make login
     *
     * @return \Bow\Database\Barry\Model
     */
    private function makeLogin()
    {
        $model = $this->provider['model'];
        $credentials = $this->provider['credentials'];
        
        $email = $credentials['username'];
        $password = $credentials['password'];

        return $model::where($this->credentials['email'], $email)->first();
    }

    private function getUserBy($key, $value)
    {
        $model = $this->provider['model'];

        return $model::where($key, $value)->first();
    }
}
