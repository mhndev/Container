<?php
namespace Poirot\Container\Interfaces;

use Poirot\Container\Exception\ContainerCreateServiceException;
use Poirot\Container\Exception\ContainerServNotFoundException;

interface iContainer
{
    /**
     * Retrieve a registered instance
     *
     * @param string $service Service name
     *
     * @throws ContainerCreateServiceException|ContainerServNotFoundException
     * @return object
     */
    function get($service);

    /**
     * Check for a registered instance
     *
     * @param string $service Service Name
     *
     * @return boolean
     */
    function has($service);
}
