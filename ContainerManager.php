<?php
namespace Poirot\Container;

use Poirot\Container\Exception\CreationException;
use Poirot\Container\Exception\NotFoundException;
use Poirot\Container\Interfaces\iContainer;
use Poirot\Container\Interfaces\iCService;
use Poirot\Container\Interfaces\iCServiceAware;
use Poirot\Container\Interfaces\iInitializer;
use Poirot\Core\Builder;

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
     * @var Builder Instance Initializer
     */
    protected $initializer;

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
     * @throws \Exception
     */
    function __construct($namespace)
    {
        // TODO: Visitor to Container Builder

        if (!is_string($namespace) || $namespace === '' )
            throw new \Exception(sprintf(
                'Namespace must be a none empty string, you injected "%s:(%s)".'
                , gettype($namespace)
                , $namespace
            ));

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
        if (!$inService->getRefreshRetrieve()) {
            // Use Retrieved Instance Before
            if (isset($this->__shared[$cName]))
                return $this->__shared[$cName];
        }

        // Refresh Service:
        try
        {
            $instance = $this->__createFromService($inService);
        }
        catch(\Exception $e) {
            throw new Exception\CreationException(sprintf(
                'An exception was raised while creating "%s"; no instance returned'
                , $name), $e->getCode(), $e);
        }

        // Store Latest Instance So Work With RefreshRetrieve Service Option
        $this->__shared[$cName] = $instance;

        return $this->__shared[$cName];
    }

        /* Create Service Instance */
        protected function __createFromService($inService)
        {
            // Initialize Service
            $this->initializer()->initialize($inService);

            // Retrieve Instance From Service
            $rInstance = $inService->createService();

            // Build Instance By Initializers:
            $this->initializer()->initialize($rInstance);

            return $rInstance;
        }

    /**
     * Builder Initializer Aggregate
     *
     * @return iInitializer
     */
    function initializer()
    {
        if (!$this->initializer) {
            $this->initializer = new ServiceInitializer();

            // add default initializer:

            // ---- All Closures Methods Bind Within Service Object
            // ---- So, $this referee to those service object
            $thisContainer = $this;
            $this->initializer->addMethod(function() use ($thisContainer) {
                if ($this instanceof iCServiceAware)
                    // Inject Service Container Inside
                    $this->setServiceContainer($thisContainer);
            }, 10000);
            // ------------------------------------------------------------
        }

        return $this->initializer;
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
