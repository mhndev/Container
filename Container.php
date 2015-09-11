<?php
namespace Poirot\Container;

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
     * @var array[ 'service' => '\interface' ]
     */
    protected $interfaces = [];

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
    protected $_tmp__canonicalNames = [];

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
        if ($cBuilder !== null)
            $cBuilder->buildContainer($this);
    }

    /**
     * Set Container Namespace
     *
     * @param string $namespace
     *
     * @throws \Exception
     * @return $this
     */
    function setNamespace($namespace)
    {
        $this->namespace = $this->__canonicalizeName($namespace);

        return $this;
    }

    /**
     * Get Container Namespace
     *
     * @return string
     */
    function getNamespace()
    {
        return $this->namespace;
    }


    // Service Manager:

    /**
     * Set Service Implementation Interface
     *
     * - interface can be
     *   string: '\InterfaceName',
     *   object: get implemented interfaces
     *   array:  combination of two both
     *
     * @param string $serviceName
     * @param string $interface
     *
     * @throws \Exception
     * @return $this
     */
    function setInterface($serviceName, $interface)
    {
        if ($this->has($serviceName))
            throw new \Exception(
                "Service ({$serviceName}) is implemented; can't define interface after set service."
            );

        if (!is_string($interface) && !interface_exists($interface))
            throw new \InvalidArgumentException(sprintf(
                'Invalid interface arguments, this must be valid interface name or an object; given (%s).'
                , \Poirot\Core\flatten($interface)
            ));

        $serviceName = $this->__canonicalizeName($serviceName);
        $this->interfaces[$serviceName] = $interface;

        return $this;
    }

    /**
     * Get Implementation Interface of Service
     *
     * @param string $serviceName
     *
     * @return string|false
     */
    function getInterfaceOf($serviceName)
    {
        $serviceName = $this->__canonicalizeName($serviceName);

        return (
            isset($this->interfaces[$serviceName])
                ? $this->interfaces[$serviceName]
                : false
        );
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

        $cName = $this->__canonicalizeName($name);
        if ($this->has($name))
            if (!$this->services[$cName]->getAllowOverride())
                throw new \Exception(sprintf(
                    'A service by the name or alias (%s) already exists and cannot be overridden; please use an alternate name',
                    $name
                ));

        $this->services[$cName] = $service;

        return $this;
    }

    /**
     * Retrieve a registered service
     *
     * - don't refresh retrieve for services, store-
     *   service on first request
     * - if service not exists ::fresh it
     *
     * note: using much argument $invOpt it's not recommended.
     *
     * @param string $serviceName Service name
     * @param mixed  $invOpt      Invoke Options
     *
     * @throws \Exception
     * @return mixed
     */
    function get($serviceName, $invOpt = [])
    {
        $cName  = $this->__canonicalizeName($serviceName);
        ## hash with options, so we get unique service with different options V
        $hashed = md5($cName.\Poirot\Core\flatten($invOpt));

        ## Service From Cache:
        if (!array_key_exists($hashed, $this->__shared)) { ### maybe null as result
            ## make new fresh instance if service not exists
            $instance = $this->fresh($serviceName, $invOpt);
            $this->__shared[$hashed] = $instance;

            ## recursion call to retrieve instance
            return $this->get($serviceName, $invOpt);
        }


        // ...

        $instance = $this->__shared[$hashed];

        # initialize retrieved service to match with defined implementation interface
        $this->__validate_interface($serviceName, $instance);
        return $instance;
    }

    /**
     * validate interface against attained service instance
     * @param string $serviceName
     * @param object|mixed $instance
     * @throws \Exception
     */
    protected function __validate_interface($serviceName, $instance)
    {
        $definedInterface = $this->getInterfaceOf($serviceName);
        if ($definedInterface === false)
            ## we have not defined interface, nothing to do
            return;

        $flag = false;
        if (is_object($instance))
            $flag = $instance instanceof $definedInterface;

        if ($flag == false)
            throw new \Exception(sprintf(
                'Service with name (%s) must implement (%s); given: %s'
                , $serviceName, $this->getInterfaceOf($serviceName), \Poirot\Core\flatten($instance)
            ));
    }

    /**
     * Retrieve a fresh instance of service
     *
     * @param string $serviceName Service name
     * @param mixed  $invOpt Invoke Options
     *
     * @throws \Exception
     * @return mixed
     */
    function fresh($serviceName, $invOpt = [])
    {
        $orgName     = $serviceName;
        $serviceName = $this->getExtendOf($serviceName);

        # check if we have alias to nested service ...................................................\
        if (substr_count($serviceName, '/', 1)) {
            // shared alias for nested container
            /* @see Container::extend */

            $xService    = explode('/', $serviceName);
            $serviceName = array_pop($xService);

            return $this->from(implode('/', $xService))->get($serviceName);
        }
        # ...........................................................................................

        if (!$this->has($serviceName))
            throw new Exception\ContainerServNotFoundException(sprintf(
                '%s "%s" was requested but no service could be found.',
                ($serviceName !== $orgName) ? "Service \"$serviceName\" with called alias"
                    : 'Service',
                $orgName
            ));


        # attain service instance ...................................................................\
        $cName = $this->__canonicalizeName($serviceName);

        /** @var iCService $inService */
        $inService = $this->services[$cName];

        # Refresh Service:
        try
        {
            ## store invokeOptions used by related initializer
            /** @see Container::initializer */
            $this->__invokeOptions = $invOpt;
            $instance = $this->__createFromService($inService);
            $this->__invokeOptions = null;
        }
        catch(\Exception $e) {
            throw new Exception\ContainerCreateServiceException(sprintf(
                'An exception was raised while creating (%s); no instance returned.'
                , $orgName
            ), $e->getCode(), $e);
        }

        # initialize retrieved service to match with defined implementation interface
        $this->__validate_interface($orgName, $instance);
        return $instance;
    }

        /* Create Service Instance */
        protected function __createFromService($inService)
        {
            ## handle errors while create service
            $this->__tmp_last_service = $inService->getName();
            set_error_handler(
                [$this, 'handle_error']
                , E_ALL ^ (E_DEPRECATED ^ E_WARNING ^ E_USER_WARNING ^ E_NOTICE)
            );

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

        /**
         * Initialize object with all parent nested initializers
         * @param mixed $inService instance created with service
         * @return mixed
         */
        function __initializeFromParents($inService)
        {
            $container   = $this;
            $initializer = $this->initializer();

            # initialize with all parent namespaces:
            while($initializer) {
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
                'Error create (%s). ("%s" on file "%s" at line %s)'
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
     * @param string $serviceName Service Name
     *
     * @return boolean
     */
    function has($serviceName)
    {
        $cName = $this->__canonicalizeName($serviceName);

        return isset($this->services[$cName]);
    }

    /**
     * Set Alias Name For Registered Service
     *
     * - Alias point can be in form of "/filesystem/system/folder"
     *   that mean, alias name is extend from "/filesystem/system/"
     *   for "folder" service
     * - Aliases Can be set even if service not found
     *   or service added later
     *
     * @param string $newName        Alias
     * @param string $serviceOrAlias Registered Service/Alias
     *
     * @throws \Exception
     * @return $this
     */
    function extend($newName, $serviceOrAlias)
    {
        if ($newName == '' || $serviceOrAlias == '')
            throw new \InvalidArgumentException('Invalid service name alias');

        if ($this->__hasAlias($newName))
            throw new \Exception(sprintf(
                'Alias name "%s" is given.'
                , $newName
            ));

        # check for registered service with same alias name:
        $cAlias = $this->__canonicalizeName($newName);
        if ($this->has($newName))
            // Alias is present as a service
            if (!$this->services[$cAlias]->getAllowOverride())
                throw new \Exception(sprintf(
                    'A service by the name "%s" already exists and cannot be overridden by Alias name; please use an alternate name',
                    $newName
                ));

        $this->aliases[$cAlias] = $serviceOrAlias;

        return $this;
    }

    /**
     * Get Extend Service Name From Service
     *
     * - if not extend any, return same service name
     *
     * @param  string $serviceName
     *
     * @return string
     */
    function getExtendOf($serviceName)
    {
        while ($this->__hasAlias($serviceName)) {
            $cAlias = $this->__canonicalizeName($serviceName);
            $serviceName  = $this->aliases[$cAlias];
            ## check if we have alias to nested service
            if (substr_count($serviceName, '/', 1))
                // we have an aliases that used as
                // share services between nested services
                // in form of "/filesystem/system/folder"
                // that mean, service is alias from "/filesystem/system/" for "folder" service
                break; ## so break iteration
        }

        return $serviceName;
    }

    /**
     * Determine if we have an alias name
     * that extend service
     *
     * @param  string $alias
     * @return bool
     */
    protected function __hasAlias($alias)
    {
        $cAlias = $this->__canonicalizeName($alias);

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
    protected function __canonicalizeName($name)
    {
        if (!is_string($name) || $name === '' )
            throw new \Exception(sprintf(
                'Name must be a none empty string, you injected "%s:(%s)".'
                , gettype($name)
                , $name
            ));

        if (isset($this->_tmp__canonicalNames[$name]))
            return $this->_tmp__canonicalNames[$name];

        $canonicalName = strtolower(
            strtr($name, [' ' => '', '\\' => self::SEPARATOR])
        );

        if (strstr($name, self::SEPARATOR) !== false)
            throw new \Exception(sprintf(
                'Service Or Alias Name Cant Contains Separation String (%s).'
                , self::SEPARATOR
            ));

        return $this->_tmp__canonicalNames[$name] = $canonicalName;
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
            : $this->__canonicalizeName($namespace);

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
        $namespace = $this->__canonicalizeName($namespace);

        if (!isset($this->__nestRight[$namespace]))
            return false;

        return $this->from($namespace);
    }
}
