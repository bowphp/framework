<?php

use Bow\Tests\Config\TestingConfiguration;

define('TESTING_RESOURCE_BASE_DIRECTORY', sprintf('%s', sys_get_temp_dir()));

require __DIR__."/../vendor/autoload.php";

Bow\Container\Action::configure([], []);
