<?php

return [
    "signkey" => "FivwuTmpJlwfXB/WMjAyMS0wMS0yNCAyMDozMTozMTE2MTE1MjAyOTEuMDEwOA==",

    /**
     * Token expiration time
     */
    "exp" => 3600 * 24 * 3,

    /**
     * Configures the issuer
     */
    "iss" => app_env("APP_JWT_ISSUER", "app.example.com"),

    /**
     * Configures the audience
     */
    "aud" => app_env("APP_JWT_AUD", "app.example.com"),

    /**
     * Token is usable after this time
     */
    "nbf" => 60,

    /**
     * Token was issue
     */
    "iat" => 60,

    /**
     * Configures the issuer
     */
    "iss" => "localhost",

    /**
     * Configures the audience
     */
    "aud" => "localhost",

    /**
     * The type of the token, which is JWT
     */
    "typ" => "JWT",

    /**
     * Hashing algorithm being used
     *
     * HS256, HS384, HS512, RS256, RS384, RS512, ES256, ES384, ES512,
     */
    "alg" => "HS256",
];
