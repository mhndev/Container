<?php
namespace Poirot\Container\Service;

/**
 * $container->set(new FactoryService([
 *       'name' => 'sysdir',
 *       'delegate' => function() {
 *           # Delegates will bind to service object as closure method
 *           # @var FactoryService $this
 *           $sc = $this->getServiceContainer();
 *
 *           # here we return service instance
 *           return $sc->from('files')->get('folder');
 *       },
 *       'refresh_retrieve' => false,
 *       'allow_override' => false
 * ]));
 */
class FactoryService extends AbstractService
{
    /**
     * @var \Closure
     */
    protected $delegate;

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
    function setDelegate(\Closure $func)
    {
        $delegate = $func->bindTo($this);
        $this->delegate = $delegate;

        return $this;
    }

    /**
     * Create Service
     *
     * @return mixed
     */
    function createService()
    {
        $func = $this->delegate;

        return $func();
    }
}
