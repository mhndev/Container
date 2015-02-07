<?php
namespace Poirot\Container;

use Poirot\Container\Interfaces\iContainer;
use Poirot\Container\Interfaces\iCService;

class ContainerManager implements iContainer
{
    /**
     * Separator between namespaces
     */
    const SEPARATOR = '/';

    /**
     * @var string Container namespace
     */
    protected $namespace = '';

    /**
     * Registered Services
     *
     * @var array[iCService]
     */
    protected $services = [];

    /**
     * @var array shared instances
     */
    protected $__shared = [];

    /**
     * @var array internal cache
     */
    protected $__canonicalNames = [];

    /**
     * Construct
     *
     * @param string $namespace
     */
    function __construct($namespace)
    {
       $this->namespace = $namespace;
    }

    /**
     * Register a service to container
     *
     * @param iCService $service Service
     *
     * @throws \Exception
     * @return $this
     */
    function set(iCService $service)
    {
        $name  = $service->getName();

        $cName = $this->canonicalizeName($name);
        if ($this->has($name))
            if (!$this->services[$cName]->getAllowOverride())
                throw new \Exception(sprintf(
                    'A service by the name or alias "%s" already exists and cannot be overridden; please use an alternate name',
                    $name
                ));

        $this->services[$cName] = $service;

        return $this;
    }

    /**
     * Retrieve a registered instance
     *
     * @param string $name Service name
     *
     * @throws CreationException|NotFoundException
     * @return mixed
     */
    function get($name)
    {
        if (!$this->has($name))
            throw new Exception\NotFoundException(sprintf(
                'An alias/service "%s" was requested but no service could be found.',
                $name
            ));

        $cName = $this->canonicalizeName($name);
        /** @var iCService $inService */
        $inService = $this->services[$cName];

        // Service From Cache:
        if (!$inService->getRefreshRetrieve())
            // Use Retrieved Instance Before
            if (isset($this->__shared[$cName]))
                return $this->__shared[$cName];

        // Refresh Service:
        try {
            // Retrieve Instance From Service
            $return = $inService->createService();
        } catch(\Exception $e) {
            throw new Exception\CreationException(sprintf(
                'An exception was raised while creating "%s"; no instance returned'
                , $name), $e->getCode(), $e);
        }

        return $return;
    }

    /**
     * Check for a registered instance
     *
     * @param string $name Service Name
     *
     * @return boolean
     */
    function has($name)
    {
        $cName = $this->canonicalizeName($name);

        return isset($this->services[$cName]);
    }

    /**
     * Canonicalize name
     *
     * @param  string $name
     * @return string
     */
    protected function canonicalizeName($name)
    {
        if (isset($this->__canonicalNames[$name]))
            return $this->__canonicalNames[$name];

        return $this->__canonicalNames[$name] = strtolower(
            strtr($name, ['-' => '', '_' => '', ' ' => '', '\\' => '', '/' => ''])
        );
    }
}
