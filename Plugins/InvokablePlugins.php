<?php
namespace Poirot\Container\Plugins;

class InvokablePlugins
{
    /**
     * @var AbstractPlugins
     */
    protected $plugins;

    /**
     * Construct
     *
     * @param AbstractPlugins $plugins
     */
    function __construct(AbstractPlugins $plugins)
    {
        $this->plugins = $plugins;
    }

    /**
     * Overloading: proxy to helpers
     *
     * Proxies to the attached plugin manager to retrieve, return, and potentially
     * execute helpers.
     *
     * * If the helper does not define __invoke, it will be returned
     * * If the helper does define __invoke, it will be called as a functor
     *
     * @param  string $method
     * @param  array $argv
     * @return mixed
     */
    function __call($method, $argv)
    {
        $options = [];
        if (count($argv) == 1 && is_array($argv[0]))
            $options = $argv[0];

        $plugin = $this->plugins->get($method, $options); // create service with arguments
        if (is_callable($plugin))
            return call_user_func_array($plugin, $argv);

        return $plugin;
    }
}
