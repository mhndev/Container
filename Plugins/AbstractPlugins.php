<?php
namespace Poirot\Container\Plugins;

use Poirot\Container\Container;
use Poirot\Container\Exception\ContainerInvalidPluginException;

abstract class AbstractPlugins extends Container
{
    /**
     * Validate Plugin Instance Object
     *
     * @param mixed $pluginInstance
     *
     * @throws ContainerInvalidPluginException
     * @return void
     */
    abstract function validatePlugin($pluginInstance);

    /**
     * Retrieve a registered instance
     *
     * @param string $service Service name
     * @param array  $invOpt  Invoke Options
     *
     * @throws ContainerInvalidPluginException
     * @return mixed
     */
    function get($service, $invOpt = [])
    {
        $return = parent::get($service, $invOpt);
        $this->validatePlugin($return);

        return $return;
    }

    /**
     * @override
     *
     * Nest A Copy Of Container Within This Container
     *
     * @param Container   $container
     * @param string|null $namespace Container Namespace
     *
     * @return $this
     */
    function nest(Container $container, $namespace = null)
    {
        if (!$container instanceof $this)
            throw new \InvalidArgumentException(sprintf(
                'Only can nest with same type pluginManager object, given "%s".'
                , get_class($container)
            ));

        return parent::nest($container, $namespace);
    }
}
