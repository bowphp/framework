<?php

namespace App\Contracts\Kernel;

interface Loader
{
    /**
     * Get app namespace
     *
     * @return array
     */
    public function namespaces();

    /**
     * The middleware collection
     *
     * @return array
     */
    public function middlewares();

    /**
     * The services collection
     *
     * @return array
     */
    public function services();

    /**
     * Load configuration
     *
     * @return Configuration
     */
    public function configurations();
}
