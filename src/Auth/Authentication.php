<?php

namespace Bow\Auth;

use Bow\Database\Barry\Model;

class Authentication extends Model
{
    /**
     * Get User id
     *
     * @return mixed
     */
    protected function getAuthenticateUserId()
    {
        return $this->attributes[$this->primaryKey];
    }
}
