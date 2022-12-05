<?php

return [
    /**
     * Security key of the application
     * Can be reorder by the command
     * <code>`php bow generate:key`</code>
     */
    'key' => __DIR__ . '/.key',

    /**
     * The Encrypt method
     */
    'cipher' => 'AES-256-CBC',

    /**
     * The Hash method
     *
     * @see https://github.com/bowphp/framework/issues/55
     */
    'hash_method' => PASSWORD_BCRYPT,

    /**
     * The Hash method options
     *
     * @see https://www.php.net/manual/fr/password.constants.php
     */
    'hash_options' => [
        'cost' => 10
    ],

    /**
     * When using token. This is the life time of a token.
     * It is strongly advised to program with tokens.
     */
    'token_expirate_time' => 50000
];
