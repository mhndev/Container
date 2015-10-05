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
use Poirot\ArgsResolver\ANamedResolver;
use Poirot\Container\Exception\ContainerServNotFoundException;
use Poirot\Container\Exception\SCInvokableCallException;
use Poirot\Container\Exception\SCInvokablePluginNotFound;

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
        try {
            $options = ($this->options) ? $this->options : [];
            $plugin = $this->plugins->get($method, $options);
        } catch (ContainerServNotFoundException $e) {
            ## service not found
            throw new SCInvokablePluginNotFound("Invokable Method ({$method}) not found as a plugin.", null , $e);
        }

        if (is_callable($plugin)) {
            if (class_exists('\Poirot\ArgsResolver\ANamedResolver\ANamedResolver')) {
                ## Resolve To Callback Arguments From Invoke Options
                try {
                    $argv = $this->__getArgsResolver()
                        ->bind($plugin)
                        ->resolve($argv)
                            ->toArray();
                } catch(\Exception $e) { }
            }

            ## handle errors
            set_error_handler(
                [$this, '__handleError']
                , E_ALL ^ (E_DEPRECATED ^ E_NOTICE)
            );
            // ..\
                $result = call_user_func_array($plugin, $argv);
            // ../
            restore_error_handler();

            return $result;
        }

        $this->options = null;
        return $plugin;
    }

        protected function __getArgsResolver()
        {
            return new ANamedResolver;
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


    // ...

    function __handleError($errno, $errstr, $errfile, $errline, $errcontext)
    {
        $errstr = "Call Error, {$errline}:\"{$errfile}\" said:({$errstr}).";

        throw new SCInvokableCallException($errstr, $errno);
    }
}
