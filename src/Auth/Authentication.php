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
     * The name of the column holding the remember-me token.
     *
     * Override in the model if your column is named differently. The application
     * must add this column to its user table: `remember_token VARCHAR(100) NULL`.
     *
     * @return string
     */
    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    /**
     * Read the current remember-me token.
     *
     * @return ?string
     */
    public function getRememberToken(): ?string
    {
        $value = $this->attributes[$this->getRememberTokenName()] ?? null;

        return is_null($value) ? null : (string) $value;
    }

    /**
     * Store a new remember-me token and persist it.
     *
     * @param  ?string $token
     * @return void
     */
    public function setRememberToken(?string $token): void
    {
        $column = $this->getRememberTokenName();

        $this->attributes[$column] = $token;
        $this->update([$column => $token]);
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
