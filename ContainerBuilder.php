<?php
namespace Poirot\Container;

use Poirot\Container\Interfaces\iContainerBuilder;
use Poirot\Container\Interfaces\iCService;
use Poirot\Container\Interfaces\iCServiceInitializer;
use Poirot\Core\AbstractOptions;

/**
$container = new ContainerManager(new ContainerBuilder([
    'namespace' => 'sysdir',
    'services'   => [
        new FactoryService(['name' => 'sysdir',
            'delegate' => function() {
                // Delegates will bind to service object as closure method
                $sc = $this->getServiceContainer();
                return $sc->from('files')->get('folder');
            },
            'refresh_retrieve' => false,
            'allow_override' => false
        ]),
 *
        // or
        'FactoryService' => [ // Prefixed Internaly with Container namespace
            'name' => 'sysdir',
            'delegate' => function() {
                // Delegates will bind to service object as closure method
                $sc = $this->getServiceContainer();
                return $sc->from('files')->get('folder');
            },
            'refresh_retrieve' => false,
            'allow_override' => false
        ],
        // or
        'Namespaces\Path\To\Service' => [
            'Option' => 'Value'
        ]
 *
    ],
    'aliases' => [
        'alias' => 'service',
    ],
    'initializers' => [
        // $priority => callable,
        // iInitializer,
        // $priority => [ // here
        //    'priority'    => 10, // or here
        //    'initializer' => callable | iInitializer, // iInitializer priority will override
        // ],
    ],
    'nested' => [
        // 'namespace' => new ContainerManager() # or instance,
        // 'namespace' => $builderArrayOption, # like this
        // $builderArrayOption, # like this
        // new ContainerManager() #or instance
    ],
]));
 */
class ContainerBuilder extends AbstractOptions
    implements iContainerBuilder
{
    protected $namespace;

    protected $services     = [];

    protected $aliases      = [];

    protected $initializers = [];

    protected $nested       = [];

    /**
     * Configure container manager
     *
     * @param Container $container
     *
     * @throws \Exception
     * @return void
     */
    function buildContainer(/*ContainerManager*/ $container)
    {
        if (!$container instanceof Container)
            throw new \Exception(sprintf(
                'Container must instanceof "ContainerManager", you given "%s".'
                , (is_object($container)) ? get_class($container) : gettype($container)
            ));

        // Namespace:
        if ($this->namespace)
            $container->setNamespace($this->namespace);

        // Initializer:
        // it become first c`use maybe used on Services Creation
        if (!empty($this->initializers))
            foreach ($this->initializers as $priority => $initializer) {
                if ($initializer instanceof iCServiceInitializer)
                    // [.. [ iInitializer, ...], ...]
                    $priority = null;
                elseif (is_array($initializer)) {
                    // [ .. [ 10 => ['priority' => 10, 'initializer' => ...], ...]
                    $priority    = (isset($initializer['priority'])) ? $initializer['priority'] : $priority;
                    $initializer = (!isset($initializer['initializer'])) ?: $initializer['initializer'];
                }

                if (is_callable($initializer))
                    $container->initializer()->addMethod($initializer, $priority);
                elseif ($initializer instanceof iCServiceInitializer)
                    $container->initializer()->addInitializer(
                        $initializer
                        , ($priority === null) ? $initializer->getDefPriority() : $priority
                    );
            }

        // Nested:
        if (!empty($this->nested))
            foreach($this->nested as $namespace => $nest) {
                if (is_array($nest))
                    $nest = new Container(new ContainerBuilder($nest));

                if (!$nest instanceof Container)
                    throw new \InvalidArgumentException(sprintf(
                        '%s: Nested container must instanceof "ContainerManager" but "%s" given.'
                        , $this->namespace, is_object($nest) ? get_class($nest) : gettype($nest)
                    ));

                if (!is_string($namespace))
                    $namespace = $nest->getNamespace();

                $container->nest($nest, $namespace);
            }

        // Aliases:
        if (!empty($this->aliases))
            foreach($this->aliases as $alias => $srv)
                $container->setAlias($alias, $srv);

        // Service:
        if (!empty($this->services))
            foreach($this->services as $key => $service) {
                if (is_string($key) && is_array($service)) {
                    // [ 'serviceClass' => [ /* options */ ], ...]
                    if (!class_exists($key) && strstr($key, '\\') === false)
                        // this is FactoryService style,
                        // must prefixed with own namespace
                        $key = '\\'.__NAMESPACE__.'\\Service\\'.$key;

                    $class = $key;
                } else {
                    // Looking For 'Sc\Services\Session' Style
                    $class   = $service;
                    $service = []; // reset service without options
                }

                if (!class_exists($class))
                    throw new \Exception($this->namespace.": Service '$key' not found as Class Name.");

                $service = new $class($service);
                if (!$service instanceof iCService)
                    throw new \InvalidArgumentException($this->namespace.": Service '$key' not recognized.");

                $container->set($service);
            }
    }

    /**
     * @param mixed $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * @param array $services
     */
    public function setServices($services)
    {
        $this->services = $services;
    }

    /**
     * @param array $aliases
     */
    public function setAliases($aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * @param array $initializers
     */
    public function setInitializers($initializers)
    {
        $this->initializers = $initializers;
    }

    /**
     * @param array $nested
     */
    public function setNested($nested)
    {
        $this->nested = $nested;
    }
}
 