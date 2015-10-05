<?php
namespace Poirot\Container\Service;

use Poirot\ArgsResolver\ANamedResolver;

/*
 * $container->set(new FunctorService([
 *       'name'     => 'serviceName',
 *       'callable' => function($arg1, $arg2) {
 *           # callable function will bind to service object as closure method
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
 * 'callable' => function($arg1, $arg2)  <---->  get('name', [12, 4])
 * 'callable' => function($arg1)         <---->  get('name', 'hello')
 *                                       <---->  get('name', ['hello'])
 * using arguments resolver:
 * @see ANamedResolver may change
 * 'callable' => function($arg1, int $x) <---->  get('name', [4, 'arg1' => '12'])
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

    /** @var callable */
    protected $callable;


    /**
     * Construct
     *
     * also can used as:
     * - new FunctorService('name', [$this, 'method']);
     * or setter set
     * - new FunctorService([ 'callable' => [..] ..options])
     *
     * @param array|callable $options
     * @param null|string    $service
     */
    function __construct($options = null, $service = null)
    {
        if (is_callable($service)) {
            ## __construct('name', [$this, 'method'])
            $this->setCallable($service);
            $this->setName($options);
        }
        else
            ## ['callable' => '..', ..]
            $this->from($options);
    }

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
    function setCallable(callable $func)
    {
        $this->callable = $func;

        return $this;
    }

    /**
     * Create Service
     *
     * @return mixed
     */
    function createService()
    {
        $callable = $this->callable;
        if ($callable instanceof \Closure)
            $callable = $callable->bindTo($this);

        if (!is_array($this->invoke_options))
            $this->invoke_options = [$this->invoke_options];

        // ...

        $arguments = $this->invoke_options;

        if (class_exists('\Poirot\ArgsResolver\ANamedResolver\ANamedResolver')) {
            ## Resolve To Callback Arguments From Invoke Options
            try {
                $arguments = $this->__getArgsResolver()
                    ->bind($callable)
                    ->resolve($this->invoke_options)
                        ->toArray();
            } catch(\Exception $e) { }
        }

        return call_user_func_array($callable, $arguments);
    }

        protected function __getArgsResolver()
        {
            return new ANamedResolver;
        }
}
