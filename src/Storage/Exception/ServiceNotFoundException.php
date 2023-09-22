<?php

declare(strict_types=1);

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
     * @return ServiceNotFoundException
     */
    public function setService(string $service_name): ServiceNotFoundException
    {
        $this->service_name = $service_name;

        return $this;
    }

    /**
     * Get the service name
     *
     * @return string
     */
    public function getService()
    {
        return $this->service_name;
    }
}
