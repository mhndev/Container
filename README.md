# Poirot\Container

Modern. Fast. Minimalism. Service Manager Container.

## Overview usage sample

```php
class defaultService extends AbstractService 
{
    /**
     * Create Service
     *
     * @return mixed
     */
    function createService()
    {
        return new Directory();
    }
}


$container = new ContainerManager('main');
$container->set(new FactoryService(['name' => 'sysdir',
    'delegate' => function() {
        // Delegates will bind to service object as closure method
        /** @var FactoryService $this */
        $sc = $this->getServiceContainer();
        return $sc->from('files')->get('folder');
    },
    'refresh_retrieve' => false,
    'allow_override' => false]
));

$nest = new ContainerManager('main');
$nest->set(new defaultService(['name' => 'directory', 'refresh_retrieve' => false, 'allow_override' => true]));
$nest->setAlias('dir', 'directory');
$nest->setAlias('folder', 'dir');
$nest->setAlias('boom', 'boomService');

$container->nest($nest, 'files');

$dir = $container->get('sysdir')
    ->scanDir();
```

## Or From Builder

```php
$container = new ContainerManager(new ContainerBuilder([
    'namespace' => 'main',
    'services'  => [
        'FactoryService' => [ // Prefixed with Container namespace
            'name' => 'sysdir',
            'delegate' => function() {
                // Delegates will bind to service object as closure method
                /** @var FactoryService $this */
                $sc = $this->getServiceContainer();
                return $sc->from('files')->get('folder');
            },
            'refresh_retrieve' => false,
            'allow_override' => false
        ],
    ],
    'nested' => [
        [
            'namespace' => 'files',
            'services'  => [
                new defaultService(['name' => 'directory'
                    , 'refresh_retrieve' => false
                    , 'allow_override' => true
                ])
            ],
            'aliases' => [
                'dir'    => 'directory',
                'folder' => 'dir',
            ],
        ],
    ],
]));

$dir = $container->get('sysdir')
    ->scanDir();
```

## Nested Hierarchy

```php
$container = new ContainerManager(new ContainerBuilder([
    'namespace' => 'main',
    'services'  => [
        'FactoryService' => [ // Prefixed with Container namespace
            'name' => 'sysdir',
            'delegate' => function() {
                // Delegates will bind to service object as closure method
                /** @var FactoryService $this */
                $sc = $this->getServiceContainer();
                return $sc->from('/filesystem/system')->get('folder'); // <<<<<=====----
            },
            'refresh_retrieve' => false,
            'allow_override' => false
        ],
    ],
    'nested' => [
        [
            'namespace' => 'filesystem',
            'nested' => [                                             // <<<<<=====----
                'system' => [
                    'services'  => [
                        new defaultService(['name' => 'directory'
                            , 'refresh_retrieve' => false
                            , 'allow_override' => true
                        ])
                    ],
                    'aliases' => [
                        'dir'    => 'directory',
                        'folder' => 'dir',
                    ],
                ],
            ],
        ],
    ],
]));
```

## Shared Service as Alias

```php
$container = new ContainerManager(new ContainerBuilder([
    'namespace' => 'main',
    'aliases' => [
        'sysdir' => ['/filesystem/system', 'folder'],  // <<<====---- Shared Alias
    ],
    'nested' => [
        [
            'namespace' => 'filesystem',
            'nested' => [
                'system' => [
                    'services'  => [
                        new defaultService(['name' => 'directory' // <<<===--- share this
                            , 'refresh_retrieve' => false
                            , 'allow_override' => true
                        ])
                    ],
                    'aliases' => [
                        'dir'    => 'directory',
                        'folder' => 'dir', // <<<===--- consumed here
                    ],
                ],
            ],
        ],
    ],
]));

/** @var Directory $dir */
$dir = $container->get('sysdir')
    ->scanDir();
```
