<?php

return [
    /**
     * The name of the session cookie
     */
    'name' => 'Bow',

    /**
     * The session driver
     */
    'driver' => 'array',

    /**
     * The session database drive option
     */
    'database' => [
        'table' => 'sessions',
        'connection' => 'sqtile'
    ],

    /**
     * The lifetime of the cookie, in seconds. See the directive
     */
    'lifetime' => 648000,

    /**
     * The path in the domain where the cookie will be accessible.
     *
     * Use a simple slash ('/') for all paths in the domain.
     *
     * @see: http://php.net/manual/fr/session.configuration.php#ini.session.cookie-path.
     */
    'path' => '/',

    /**
     * The cookie domain, for example 'www.example.com'.
     * To make cookies visible on all subdomains,
     * the domain must be prefixed with a dot, such as '.example.com'.
     *
     * @see http://php.net/manual/fr/session.configuration.php#ini.session.cookie-domain
     */
    'domain' => null,

    /**
     * If true, the cookie will only be sent over a secure connection.
     *
     * @see: http://php.net/manual/fr/session.configuration.php#ini.session.cookie-secure
     */
    'secure' => false,

    /**
     * If true, PHP will attempt to send the httponly option when configuring the cookie.
     *
     * @see http://php.net/manual/fr/session.configuration.php#ini.session.cookie-httponly
     */
    'httponly' => false,

    /**
     * Session data path.
     * If path is specified, the path of the folder will be changed.
     *
     * On some operating systems, you will have to choose a path to a folder
     * able to handle a large number of small files efficiently.
     * For example, on Linux, reiserfs can be more efficient than ext2fs.
     */
    'save_path' => TESTING_RESOURCE_BASE_DIRECTORY . '/session',
];
