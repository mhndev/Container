<?php
namespace Poirot\Container;

use Poirot\Container\Interfaces\iCServiceInitializer;

class ContainerServiceInitializer implements iCServiceInitializer
{
    protected $priorityQueue;

    /**
     * Add Callable Method
     *
     * Methods will bind to service immediately:
     * [code]
     * function() {
     *  if ($this instanceof Application\ModuleAsService)
     *      // do something
     * }
     * [/code]
     *
     * !! and get service as first argument if service not object
     * function($service)
     *
     * @param callable $methodCallable  Callable
     * @param int      $priority        Priority
     *
     * @return $this
     */
    function addMethod($methodCallable, $priority = 10)
    {
        if (!is_callable($methodCallable))
            throw new \InvalidArgumentException('Param must be callable');

        $this->queue()->insert($methodCallable, $priority);

        return $this;
    }

    /**
     * Add Initializer Interface
     * Add Initializer Interface
     *
     * @param iCServiceInitializer $initializer Initializer Interface
     * @param null|int     $priority    Priority
     *
     * @return $this
     */
    function addInitializer(iCServiceInitializer $initializer, $priority = null)
    {
        $priority = ($priority == null)
            ? ( ($initializer->getPriority() !== null) ? $initializer->getPriority() : 10)
            : $priority;

        $this->queue()->insert($initializer, $priority);

        return $this;
    }

    /**
     * Initialize Service
     *
     * @param mixed $service Service
     *
     * @return mixed
     */
    function initialize($service)
    {
        foreach(clone $this->queue() as $initializer)
        {
            // TODO: Handle Exception Retrieval

            if ($initializer instanceof \Closure) {
                /** @var \Closure $initializer */
                if (is_object($service)) {
                    // Bind Initializer within service
                    $initializer = $initializer->bindTo($service);
                    $initializer();
                } else {
                    // initializer($service)
                    call_user_func_array($initializer, [&$service]);
                }
            }
            elseif ($initializer instanceof iCServiceInitializer)
                $initializer->initialize($service);
            else
                throw new \InvalidArgumentException(sprintf(
                    'Invalid Initializer Provided. (%s)'
                    , serialize($initializer)
                ));
        }
    }

    /**
     * Used To Bind Initializer
     *
     * @return int
     */
    function getDefPriority()
    {
        return 0;
    }

    /**
     * internal priority queue
     *
     * @return \SplPriorityQueue
     */
    protected function queue()
    {
        if (!$this->priorityQueue)
            $this->priorityQueue = new \SplPriorityQueue();

        return $this->priorityQueue;
    }
}
