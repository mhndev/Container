<?php
namespace Poirot\Container\Interfaces\Plugins;

use Poirot\Container\Plugins\AbstractPlugins;

interface iPluginManagerAware
{
    /**
     * Set Plugins Manager
     *
     * @param AbstractPlugins $plugins
     *
     * @return $this
     */
    function setPluginManager(AbstractPlugins $plugins);
}
