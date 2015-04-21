<?php
namespace Poirot\Container\Interfaces\Respec;

use Poirot\Container\Interfaces\iContainer;

interface iCServiceProvider
{
    /**
     * Get Service Container
     *
     * @return iContainer
     */
    function getServiceContainer();
}
