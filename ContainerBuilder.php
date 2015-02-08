<?php
namespace Poirot\Container;

use Poirot\Container\Interfaces\iContainerBuilder;
use Poirot\Core\AbstractOptions;

/**
$container = new ContainerManager(new ContainerBuilder([
    'namespace' => 'sysdir',
    'service'   => [
        new FactoryService(['name' => 'sysdir',
            'delegate' => function() {
                // Delegates will bind to service object as closure method
                $sc = $this->getServiceContainer();
                return $sc->from('files')->get('folder');
            },
            'refresh_retrieve' => false,
            'allow_override' => false
        ]),
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
    ],
    'alias' => [
        'alias' => 'service',
    ],
    'initializer' => [
        // priority => callable | iInitializer,
        // iInitializer,
        // [
        //    priority    => 10,
        //    initializer => callable | iInitializer,
        // ],
    ],
    'nest' => [
        // 'namespace' => new ContainerManager() # or instance,
        // new ContainerManager() #or instance
        // 'namespace' => $builderArrayOption, # like this
    ],
]));
 */
class ContainerBuilder extends AbstractOptions
    implements iContainerBuilder
{
    protected $namespace;

    protected $service = [];

    protected $initializer = [];

    /**
     * Configure container manager
     *
     * @param ContainerManager $container
     * @return void
     */
    function buildContainer(ContainerManager $container)
    {
        // TODO: Implement buildContainer() method.
    }

    // ContainerManager Implementation Options:

}
 