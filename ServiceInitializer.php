<?php
namespace Poirot\Container;

use Poirot\Container\Interfaces\iInitializer;

class ServiceInitializer implements iInitializer
{
    protected $priorityQueue;

    /**
     * Add Callable Method
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
     * @param iInitializer $initializer Initializer Interface
     * @param null|int     $priority    Priority
     *
     * @return $this
     */
    function addInitializer(iInitializer $initializer, $priority = null)
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
        $prioQueue = clone $this->queue();

        foreach($prioQueue as $int)
        {
            // TODO: Exception Retrieval
            // TODO: INITIALIZER

            if (is_callable($int))
                // func($service)
                call_user_func_array($int, [$service]);
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
