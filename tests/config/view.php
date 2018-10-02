<?php
return [

    // mustache, pug, php
    // mustache: mustache/mustache
    // pug: pug-php/pug
    'engine' => 'twig',

    // Extension des pages de vues
    'extension' => '.twig',

    // Le repertoire de cache.
    'cache' => dirname(__DIR__) . '/data/cache',

    // Le repertoire des vues.
    'path' => dirname(__DIR__) . '/data/view',

    'additionnal_options' => [
        'auto_reload' => true
    ]
];
