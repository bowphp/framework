<?php

declare(strict_types=1);

namespace Bow\Auth;

use Bow\Database\Barry\Model;

class Authentication extends Model
{
    /**
     * Get the user id
     *
     * @return mixed
     */
    public function getAuthenticateUserId()
    {
        return $this->attributes[$this->primary_key];
    }
}
