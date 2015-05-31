<?php
namespace Poirot\Container\Interfaces\Plugins;

use Poirot\Container\Plugins\InvokablePlugins;

interface iInvokePluginsProvider
{
    /**
     * Plugin Manager
     *
     * @return InvokablePlugins
     */
    function plugin();
}
