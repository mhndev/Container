<?php
namespace Poirot\Container\Interfaces;

interface iInitializer 
{
    /**
     * Initialize Service
     *
     * @param mixed $service Service
     *
     * @return mixed
     */
    function initialize($service);

    /**
     * Used To Bind Initializer
     *
     * @return int
     */
    function getDefPriority();
}
