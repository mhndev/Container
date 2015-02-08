<?php
namespace Poirot\Container;

use Poirot\Container\Interfaces\iContainer;
use Poirot\Container\Interfaces\iContainerBuilder;
use Poirot\Core\AbstractOptions;

class BuildContainer extends AbstractOptions
    implements iContainerBuilder
{
    /**
     * Configure container manager
     *
     * @param iContainer $container
     * @return void
     */
    function buildContainer(iContainer $container)
    {
        // TODO: Implement buildContainer() method.
    }

    
}
 