<?php
namespace Poirot\Container\Interfaces;

use Poirot\Container\Exception\CreationException;
use Poirot\Container\Exception\NotFoundException;

interface iContainer
{
    /**
     * Retrieve a registered instance
     *
     * @param string $service Service name
     *
     * @throws CreationException|NotFoundException
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
