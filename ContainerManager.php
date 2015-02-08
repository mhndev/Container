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
     * @var array Service Aliases
     */
    protected $aliases = [];

    /**
     * @var array internal cache
     */
    protected $__canonicalNames = [];

    /**
     * @var array Child Nested Containers
     */
    protected $__nestRight = [];

    /**
     * @var null Container That Nested To
     */
    protected $__nestLeft = null;


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

        $this->__setNamespace($namespace);
    }

        protected function __setNamespace($namespace)
        {
            $this->namespace = $namespace;
        }

    // Service Manager:

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
     * Set Alias Name For Registered Service
     *
     * - Aliases Can be set even if service not found
     *   or service added later
     *
     * @param string $alias          Alias
     * @param string $serviceOrAlias Registered Service Name/Alias
     *
     * @throws \Exception
     * @return $this
     */
    function setAlias($alias, $serviceOrAlias)
    {
        if ($alias == '' || $serviceOrAlias == '')
            throw new \InvalidArgumentException('Invalid service name alias');

        if ($this->hasAlias($alias))
            throw new \Exception('Invalid service name alias');

        $cAlias = $this->canonicalizeName($alias);
        $this->aliases[$cAlias] = $serviceOrAlias;

        return $this;
    }

    /**
     * Get Service Name Of Alias Point
     *
     * - return same name if alias not present
     *
     * @param  string $alias
     *
     * @return false | string
     */
    function getAliasPoint($alias)
    {
        while ($this->hasAlias($alias)) {
            $cAlias = $this->canonicalizeName($alias);
            $alias  = $this->aliases[$cAlias];
        }

        return $alias;
    }

    /**
     * Determine if we have an alias
     *
     * @param  string $alias
     * @return bool
     */
    function hasAlias($alias)
    {
        $cAlias = $this->canonicalizeName($alias);

        return isset($this->aliases[$cAlias]);
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

    // Nested Containers:

    /**
     * Nest A Copy Of Container Within This Container
     *
     * @param ContainerManager $container
     * @param string|null      $namespace Container Namespace
     *
     * @return $this
     */
    function nest(ContainerManager $container, $namespace = null)
    {
        // Use Container Namespace if not provided as argument
        $namespace = ($namespace === null) ? $container->namespace : $namespace;

        if (isset($this->__nestRight[$namespace]))
            throw new \InvalidArgumentException(sprintf(
                'Namespace "%s" is exists on container:%s'
                , $namespace , $this->namespace
            ));

        $nestedCnt = clone $container;
        $nestedCnt->__nestLeft = $this; // set parent container
        $nestedCnt->__setNamespace($namespace);

        $this->__nestRight[$namespace] = $nestedCnt;

        return $this;
    }

    /**
     * Retrieve Nested Container
     *
     * @param string $namespace
     *
     * @throws \Exception On Namespace not found
     * @return ContainerManager
     */
    function from($namespace)
    {
        if (!isset($this->__nestRight[$namespace]))
            throw new \Exception(sprintf(
                'No nested container found for "%s".'
                , $namespace
            ));

        return $this->__nestRight[$namespace];
    }

    /**
     * Retrieve Or Build Nested Container
     *
     * @param string $namespace
     *
     * @return ContainerManager
     */
    function with($namespace)
    {
        if (!isset($this->__nestRight[$namespace])) {
            $this->nest(new $this(), $namespace);
        }

        return $this->from($namespace);
    }
}
