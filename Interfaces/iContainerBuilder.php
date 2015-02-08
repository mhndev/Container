<?php
namespace Poirot\Container\Interfaces;

interface iContainerBuilder
{
    /**
     * Configure container manager
     *
     * @param iContainer $container
     * @return void
     */
    function buildContainer(iContainer $container);
}
