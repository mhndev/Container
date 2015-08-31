<?php
namespace Poirot\Container;

use Poirot\Container\Exception\ContainerCreateServiceException;
use Poirot\Container\Exception\ContainerServNotFoundException;
use Poirot\Container\Interfaces\iContainer;
use Poirot\Container\Interfaces\iContainerBuilder;
use Poirot\Container\Interfaces\iCService;
use Poirot\Container\Interfaces\Respec\iCServiceAware;
use Poirot\Container\Service\AbstractService;

class Container implements iContainer
{
    /**
     * Separator between namespaces
     */
    const SEPARATOR = '/';

    /**
     * @var string Container namespace
     */
    protected $namespace;

    /**
     * Registered Services
     * @var array[iCService]
     * canonicalized names
     */
    protected $services = [];

    /**
     * @var ContainerServiceInitializer Instance Initializer
     */
    protected $initializer;

    /**
     * @var array shared instances
     * canonicalized names
     */
    protected $__shared = [];

    /**
     * @var array Service Aliases
     * canonicalized names
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
     * @var null|Container Container That Nested To
     */
    protected $__nestLeft = null;

    /**
     * @var array Create instance invoke options
     */
    protected $__invokeOptions;


    /**
     * Construct
     *
     * @param iContainerBuilder $cBuilder
     *
     * @throws \Exception
     */
    function __construct(iContainerBuilder $cBuilder = null)
    {
        if ($cBuilder)
            $cBuilder->buildContainer($this);
    }

    function setNamespace($namespace)
    {
        $this->namespace = $this->canonicalizeName($namespace);
    }

    function getNamespace()
    {
        return $this->namespace;
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
     * @param string $service Service name
     * @param array  $invOpt  Invoke Options
     *
     * @throws ContainerCreateServiceException|ContainerServNotFoundException
     * @return mixed
     */
    function get($service, $invOpt = [])
    {
        $name = $service;
        $orgName = $name;
        $name = $this->getAliasPoint($name);
        if (is_array($name))
            // shared alias for nested container
            /* @see setAlias */
            return $this->from($name[0])->get($name[1]);

        if (!$this->has($name))
            throw new Exception\ContainerServNotFoundException(sprintf(
                '%s "%s" was requested but no service could be found.',
                ($name !== $orgName) ? "Service \"$name\" with called alias"
                    : 'Service',
                $orgName
            ));

        $cName = $this->canonicalizeName($name);
        /** @var iCService $inService */
        $inService = $this->services[$cName];

        # we want fresh shared for each service with new options
        $hashed = md5($cName.\Poirot\Core\flatten($invOpt));

        // Service From Cache:
        if (!$inService->getRefreshRetrieve()) {
            // Use Retrieved Instance Before
            if (isset($this->__shared[$hashed]))
                return $this->__shared[$hashed];
        }

        // Refresh Service:
        try
        {
            $this->__invokeOptions = $invOpt;

            $instance = $this->__createFromService($inService);

            $this->__invokeOptions = null;
        }
        catch(\Exception $e) {
            throw new Exception\ContainerCreateServiceException(sprintf(
                'An exception was raised while creating "%s"; no instance returned'
                , $name
            ), $e->getCode(), $e);
        }

        // Store Latest Instance So Work With RefreshRetrieve Service Option
        $this->__shared[$hashed] = $instance;

        return $this->__shared[$hashed];
    }

        /* Create Service Instance */
        protected function __createFromService($inService)
        {
            $this->__tmp_last_service = $inService->getName();
            set_error_handler([$this, 'handle_error'], E_ALL);

            // Initialize Service
            ## first initialize service creator factory class
            $this->__initializeFromParents($inService);

            // Retrieve Instance From Service
            $rInstance = $inService->createService();
            // Build Instance By Initializers:
            $this->__initializeFromParents($rInstance);

            restore_error_handler();
            unset($this->__tmp_last_service);

            return $rInstance;
        }

        function __initializeFromParents($inService)
        {
            $container   = $this;
            $initializer = $this->initializer();

            while($initializer) {
                ## initialize with all parent namespaces:
                $initializer->initialize($inService);

                if ($container->__nestLeft) {
                    $container   = $container->__nestLeft;
                    $initializer = $container->initializer();
                }
                else
                    $initializer = false;
            }

            return $inService;
        }

        function handle_error($errno, $errstr, $errfile, $errline, $errcontext)
        {
            $errstr = sprintf(
                'Error create "%s". ("%s" on file "%s" at line %s)'
                , $this->__tmp_last_service, $errstr, $errfile, $errline
            );
            throw new \Exception($errstr, $errno);
        }

    /**
     * Builder Initializer Aggregate
     *
     * @return ContainerServiceInitializer
     */
    function initializer()
    {
        if (!$this->initializer) {
            $this->initializer = new ContainerServiceInitializer();

            // add default initializer:

            // ---- All Closures Methods Bind Within Service Object
            // ---- So, $this referee to those service object
            $thisContainer = $this;
            $this->initializer->addMethod(function() use ($thisContainer) {
                if ($this instanceof iCServiceAware)
                    // Inject Service Container Inside
                    $this->setServiceContainer($thisContainer);
            }, 10000);

            // Inject Invoke Parameters into service to build:
            $self = $this;
            $this->initializer->addMethod(function() use ($self) {
                if ($this instanceof iCService) {
                    /** @var AbstractService $this */
                    $this->invoke_options = $self->__invokeOptions;
                }
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
     * - Alias point can be in form of ['/filesystem/system', 'folder'],
     *   that mean, alias name is alias from /filesystem/system/
     *   for folder service
     * - Aliases Can be set even if service not found
     *   or service added later
     *
     * @param string $alias          Alias
     * @param string $serviceOrAlias Registered Service/Alias
     *                               - in form of 'sysdir' => ['/filesystem/system', 'folder'],
     *                                 that mean, sysdir is alias from /filesystem/system/ for folder service
     *
     * @throws \Exception
     * @return $this
     */
    function setAlias($alias, $serviceOrAlias)
    {
        if ($alias == '' || $serviceOrAlias == '')
            throw new \InvalidArgumentException('Invalid service name alias');

        if ($this->hasAlias($alias))
            throw new \Exception(sprintf(
                'Alias name "%s" is given.'
                , $alias
            ));

        $cAlias = $this->canonicalizeName($alias);
        if ($this->has($alias))
            // Alias is present as a service
            if (!$this->services[$cAlias]->getAllowOverride())
                throw new \Exception(sprintf(
                    'A service by the name "%s" already exists and cannot be overridden by Alias name; please use an alternate name',
                    $alias
                ));

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
            if (is_array($alias))
                // we have an aliases that used as
                // share services between nested services
                // in form of 'sysdir' => ['/filesystem/system', 'folder'],
                // that mean, sysdir is alias from /filesystem/system/ for folder service
                break;
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
     * - the name can't contains separate(/) string
     *
     * @param  string $name
     *
     * @throws \Exception
     * @return string
     */
    protected function canonicalizeName($name)
    {
        if (!is_string($name) || $name === '' )
            throw new \Exception(sprintf(
                'Name must be a none empty string, you injected "%s:(%s)".'
                , gettype($name)
                , $name
            ));

        if (isset($this->__canonicalNames[$name]))
            return $this->__canonicalNames[$name];

        $canonicalName = strtolower(
            strtr($name, [' ' => '', '\\' => self::SEPARATOR])
        );

        if (strstr($name, self::SEPARATOR) !== false)
            throw new \Exception(sprintf(
                'Service Or Alias Name Cant Contains Separation String (%s).'
                , self::SEPARATOR
            ));

        return $this->__canonicalNames[$name] = $canonicalName;
    }

    // Nested Containers:

    /**
     * Nest A Copy Of Container Within This Container
     *
     * @param Container   $container
     * @param string|null $namespace Container Namespace
     *
     * @return $this
     */
    function nest(Container $container, $namespace = null)
    {
        // Use Container Namespace if not provided as argument
        $namespace = ($namespace === null) ? $container->getNamespace()
            : $this->canonicalizeName($namespace);

        if ($namespace === null || $namespace === '')
            throw new \InvalidArgumentException(sprintf(
                'Namespace can`t be empty And Must Set.'
                , $namespace , $this->getNamespace()
            ));

        if (isset($this->__nestRight[$namespace]))
            throw new \InvalidArgumentException(sprintf(
                'Namespace "%s" is exists on container:%s'
                , $namespace , $this->getNamespace()
            ));

        $nestedCnt = clone $container;
        $nestedCnt->__nestLeft = $this; // set parent container
        $nestedCnt->setNamespace($namespace);

        $this->__nestRight[$namespace] = $nestedCnt;

        return $this;
    }

    /**
     * Retrieve Nested Container
     *
     * @param string $namespace
     *
     * @throws \Exception On Namespace not found
     * @return Container
     */
    function from($namespace)
    {
        if ($namespace === '')
            # from recursion calls
            return $this;
        elseif (!strstr($namespace, self::SEPARATOR)) {
            if (!isset($this->__nestRight[$namespace]))
                throw new \Exception(sprintf(
                    'Namespace "%s" not found on "%s".'
                    , $namespace , get_class($this)
                ));

            return $this->__nestRight[$namespace];
        }

        $namespace    = rtrim($namespace, self::SEPARATOR);
        $brkNamespace = explode(self::SEPARATOR, $namespace);

        $cNamespace   = array_shift($brkNamespace);
        $cContainer   = $this;
        if ($cNamespace === '') {
            // Goto Root Container
            while ($cContainer->__nestLeft)
                $cContainer = $cContainer->__nestLeft;
        }
        else
            $cContainer = $this->from($cNamespace);

        return $cContainer->from(implode(self::SEPARATOR, $brkNamespace));
    }

    /**
     * Retrieve Or Build Nested Container
     *
     * @param string $namespace
     *
     * @return Container|false
     */
    function with($namespace)
    {
        $namespace = $this->canonicalizeName($namespace);

        if (!isset($this->__nestRight[$namespace]))
            return false;

        return $this->from($namespace);
    }
}
