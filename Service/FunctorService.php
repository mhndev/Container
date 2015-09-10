<?php
namespace Poirot\Container\Service;

use Poirot\ArgsResolver\ANamedResolver;

/*
 * $container->set(new FunctorService([
 *       'name'     => 'serviceName',
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
 * $container->get('serviceName', [$arg1Val, $arg2Val]);
 *
 * ...........................................................................
 * 'callback' => function($arg1, $arg2)  <---->  get('name', [12, 4])
 * 'callback' => function($arg1)         <---->  get('name', 'hello')
 *                                       <---->  get('name', ['hello'])
 * using arguments resolver:
 * @see ANamedResolver may change
 * 'callback' => function($arg1, int $x) <---->  get('name', [4, 'arg1' => '12'])
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
    function setCallback(callable $func)
    {
        $this->callback = $func;

        return $this;
    }

    /**
     * Create Service
     *
     * @return mixed
     */
    function createService()
    {
        $callback = $this->callback;
        if ($callback instanceof \Closure)
            $callback = $callback->bindTo($this);

        if (!is_array($this->invoke_options))
            $this->invoke_options = [$this->invoke_options];

        // ...

        $arguments = $this->invoke_options;

        if (class_exists('\Poirot\ArgsResolver\ANamedResolver\ANamedResolver')) {
            ## Resolve To Callback Arguments From Invoke Options
            try {
                $arguments = $this->__getArgsResolver()
                    ->bind($callback)
                    ->resolve($this->invoke_options)
                        ->toArray();
            } catch(\Exception $e) { }
        }

        return call_user_func_array($callback, $arguments);
    }

    protected function __getArgsResolver()
    {
        return new ANamedResolver;
    }
}
