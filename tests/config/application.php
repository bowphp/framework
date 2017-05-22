<?php
return [

    // mustache, pug, php
    // mustache: mustache/mustache
    // pug: pug-php/pug
    'template_engine' => 'twig',

    // Extension des pages de vues
    'template_extension' => '.twig',

    // Le repertoire de cache.
    'template_cache_folder' => dirname(__DIR__) . '/data/cache',

    // Disponible seulement pour twig
    'template_auto_reload_cache_views' => true,

    // Le repertoire des vues.
    'views_path' => dirname(__DIR__) . '/data/view'
];
