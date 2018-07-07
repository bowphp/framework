<?php

require __DIR__."/../../vendor/autoload.php";

Bow\Config\Config::configure(__DIR__.'/config');

Bow\Application\Actionner::configure([], []);
