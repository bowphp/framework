<?php

require __DIR__."/../vendor/autoload.php";

Bow\Configuration\Loader::configure(__DIR__.'/config');

Bow\Container\Action::configure([], []);

Bow\Database\Database::configure(require __DIR__.'/config/database.php');
