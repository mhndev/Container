<?php
namespace Poirot\Container\Interfaces;

use Poirot\Core\Interfaces\iBuilderSetter;

interface iContainerBuilder extends iBuilderSetter
{
    /**
     * Configure container manager
     *
     * @param iContainer $container
     */
    function buildContainer(/*iContainer*/ $container);
}
