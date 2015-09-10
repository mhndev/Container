<?php
namespace Poirot\Container\Interfaces;

use Poirot\Container\Exception\ContainerCreateServiceException;
use Poirot\Container\Exception\ContainerServNotFoundException;

interface iContainer
{
    /**
     * Retrieve a registered service
     *
     * - don't refresh retrieve for services, store-
     *   service on first request
     * - if service not exists ::fresh it
     *
     * @param string $serviceName Service name
     * @param array  $invOpt      Invoke Options
     *
     * @throws ContainerCreateServiceException|ContainerServNotFoundException
     * @return mixed
     */
    function get($serviceName, $invOpt = []);

    /**
     * Retrieve a fresh instance of service
     *
     * @param string $serviceName Service name
     * @param array  $invOpt      Invoke Options
     *
     * @throws ContainerCreateServiceException|ContainerServNotFoundException
     * @return mixed
     */
    function fresh($serviceName, $invOpt = []);

    /**
     * Check for a registered instance
     *
     * @param string $serviceName Service Name
     *
     * @return boolean
     */
    function has($serviceName);
}
