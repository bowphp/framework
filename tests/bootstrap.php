<?php

define('TESTING_RESOURCE_BASE_DIRECTORY', sprintf('%s/bowphp_testing', sys_get_temp_dir()));

if (!is_dir(TESTING_RESOURCE_BASE_DIRECTORY)) {
    mkdir(TESTING_RESOURCE_BASE_DIRECTORY, 0777, true);
}

require __DIR__ . "/../vendor/autoload.php";

/*
| Silence PHP 8.4's "implicitly nullable parameter" deprecations that
| originate in third-party vendor code we cannot upgrade:
|
|   - spatie/phpunit-snapshot-assertions 4.2.17 (last of the 4.x line;
|     5.x needs PHPUnit 10)
|   - lcobucci/jwt 3.2.5 (pinned by bowphp/policier)
|
| Framework-code deprecations are NOT silenced — they fall through to PHP's
| default handler so we still see anything that needs fixing in src/.
*/
set_error_handler(static function (int $severity, string $message, string $file): bool {
    $vendor_deprecation = ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED)
        && str_contains($file, '/vendor/');

    return $vendor_deprecation; // true = swallow; false = let PHP handle it
});
