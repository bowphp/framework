<?php

use Bow\Tests\Auth\Stubs\UserModelStub;

return [
    /**
     * Branch by default of connection
     */
    "default" => "web",

    /**
     * Default authentication branch
     */
    "web" => [
        "type" => "session",
        'model' => UserModelStub::class,
        'credentials' => [
            'username' => 'username',
            'password' => 'password'
        ]
    ],

    /**
     * Default authentication branch
     */
    "api" => [
        "type" => "jwt",
        'model' => UserModelStub::class,
        'credentials' => [
            'username' => 'username',
            'password' => 'password'
        ]
    ],
];
