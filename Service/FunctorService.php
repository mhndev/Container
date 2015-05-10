<?php
namespace Poirot\Container\Service;

/**
 * $container->set(new FunctorService([
 *       'name'     => 'sysdir',
 *       'callback' => function($arg1, $arg2, ...) {
 *           # callback function will bind to service object as closure method
 *           # so you can access methods from FunctorService
 *           $sc = $this->getServiceContainer();
 *
 *           # here we return service result
 *           return $arg1.' '.$arg2;
 *       },
 *       'refresh_retrieve' => true,
 *       'allow_override'   => false
 * ]));
 */
class FunctorService extends AbstractService
{
    /**
     * @var \Closure
     */
    protected $callback;

    /**
     * Set createService Delegate
     *
     * - it will bind to service object as closure method
     *   so, you can access to methods from FactoryService
     *   from function() { $this->getServiceContainer() }
     *
     * @param callable $func
     *
     * @return $this
     */
    function setCallback(\Closure $func)
    {
        $delegate = $func->bindTo($this);
        $this->callback = $delegate;

        return $this;
    }

    /**
     * Create Service
     *
     * @return mixed
     */
    function createService()
    {
        $func = $this->callback;

        return $func();
    }
}
