<?php

return [
    // twig, tintin, php
    'engine' => 'twig',

    // Extension des pages de vues
    'extension' => '.twig',

    // Le repertoire de cache.
    'cache' => TESTING_RESOURCE_BASE_DIRECTORY . '/cache',

    // Le repertoire des vues.
    'path' => __DIR__ . '/../../View/stubs',

    'additionnal_options' => [
        'auto_reload' => true
    ]
];
