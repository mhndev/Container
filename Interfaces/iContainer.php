<?php
namespace Poirot\Container\Interfaces;

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
