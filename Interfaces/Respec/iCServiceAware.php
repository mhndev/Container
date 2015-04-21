<?php
namespace Poirot\Container\Interfaces\Respec;

use Poirot\Container\Interfaces\iContainer;

/**
 * Interface iCServiceAware
 *
 * - Classes that implement this interface
 *   can have parent Service Container injected
 *
 */
interface iCServiceAware 
{
    /**
     * Set Service Container
     *
     * @param iContainer $container
     */
    function setServiceContainer(iContainer $container);
}
