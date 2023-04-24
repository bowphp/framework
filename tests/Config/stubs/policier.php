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
];
