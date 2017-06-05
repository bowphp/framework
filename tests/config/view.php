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

    // Disponible seulement pour twig
    'auto_reload_cache' => true,

    // Le repertoire des vues.
    'path' => dirname(__DIR__) . '/data/view'
];
