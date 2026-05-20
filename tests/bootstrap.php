<?php

define('TESTING_RESOURCE_BASE_DIRECTORY', sprintf('%s/bowphp_testing', sys_get_temp_dir()));

if (!is_dir(TESTING_RESOURCE_BASE_DIRECTORY)) {
    mkdir(TESTING_RESOURCE_BASE_DIRECTORY, 0777, true);
}

require __DIR__ . "/../vendor/autoload.php";
