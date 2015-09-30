<?php
namespace Poirot\Container\Interfaces;

interface iContainerBuilder
{
    /**
     * Configure container manager
     *
     * @param iContainer $container
     */
    function build(/*iContainer*/ $container);
}
