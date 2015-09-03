<?php
namespace Poirot\Container\Plugins;

/*
$invokablePlugins
    ->options([
        ## using to get service from container
        'key' => 'value',
    ])
    ->callPlugin('arg1', 2);
*/
class InvokablePlugins
{
    /** @var AbstractPlugins */
    protected $plugins;
    /** @var array|null */
    protected $options = null;

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
     * * If the helper does     define __invoke, it will be called as a functor
     *
     * @param  string $method
     * @param  array $argv
     * @return mixed
     */
    function __call($method, $argv)
    {
        $options = ($this->options) ? $this->options : [];
        $plugin = $this->plugins->get($method, $options);

        if (is_callable($plugin))
            return call_user_func_array($plugin, $argv);

        $this->options = null;
        return $plugin;
    }

    /**
     * Options to get service
     *
     * $this->action('/module/application')
     *   ->options([
     *      'key' => 'value',
     *   ])
     *   ->thenCall()
     *
     * @param array $options
     *
     * @return $this
     */
    function options(array $options)
    {
        $this->options = $options;

        return $this;
    }
}
