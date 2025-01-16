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
    public function getAuthenticateUserId(): mixed
    {
        return $this->attributes[$this->primary_key];
    }

    /**
     * Define the additional values
     *
     * @return array
     */
    public function customJwtAttributes(): array
    {
        return [];
    }
}
