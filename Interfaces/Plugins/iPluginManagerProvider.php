<?php
namespace Poirot\Container\Interfaces\Plugins;

use Poirot\Container\Plugins\AbstractPlugins;

interface iPluginManagerProvider
{
    /**
     * Get Plugins Manager
     *
     * @return AbstractPlugins
     */
    function getPluginManager();
}
