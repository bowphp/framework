<?php

namespace Bow\Storage\Exception;

use ErrorException;

class ServiceNotFoundException extends ErrorException
{
    /**
     * The service name
     *
     * @var string
     */
    private $service_name;

    /**
     * Set the service name
     *
     * @param string $service_name
     *
     * @return void
     */
    public function setService($service_name)
    {
        $this->service_name = $service_name;
    }

    /**
     * Get the service name
     *
     * @return void
     */
    public function getService()
    {
        return $this->service_name;
    }
}
