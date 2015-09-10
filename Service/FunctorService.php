<?php
namespace Poirot\Container\Service;

/**
 * $container->set(new FunctorService([
 *       'name'     => 'service_name',
 *       'callback' => function($arg1, $arg2) {
 *           # callback function will bind to service object as closure method
 *           # so you can access methods from FunctorService
 *           $sc = $this->getServiceContainer();
 *
 *           # here we return service result
 *           return $arg1.' '.$arg2;
 *       },
 *       'allow_override'   => false
 * ]));
 *
 * $container->new('service_name', [$arg1Val, $arg2Val]);
 *
 */
class FunctorService extends AbstractService
{
    /**
     * Function Arguments as a Container::get arg. options
     *
     * @var array
     * @see Container::initializer
     * @see Container::get
     */
    public $invoke_options;

    /**
     * @var \Closure
     */
    protected $callback;

    /**
     * Indicate to retrieve refresh instance
     * on creating service
     *
     * @var bool
     */
    protected $refreshInstance = true;

    /**
     * Set createService Delegate
     *
     * - it will bind to service object as closure method
     *   so, you can access to methods from FunctorService
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

        return call_user_func_array($func, $this->invoke_options);
    }
}
