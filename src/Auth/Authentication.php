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
    public function getAuthenticateUserId()
    {
        return $this->attributes[$this->primary_key];
    }
}
