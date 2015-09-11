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


$container = new Container('main');
$container->set(new FactoryService(['name' => 'sysdir',
    'delegate' => function() {
        // Delegates will bind to service object as closure method
        /** @var FactoryService $this */
        $sc = $this->getServiceContainer();
        return $sc->from('files')->get('folder');
    },
    'allow_override' => false]
));

$nest = new Container('main');
$nest->set(new defaultService(['name' => 'directory', 'allow_override' => true]));
$nest->setAlias('dir', 'directory');
$nest->setAlias('folder', 'dir');
$nest->setAlias('boom', 'boomService');

$container->nest($nest, 'files');

$dir = $container->get('sysdir')
    ->scanDir();
```

## Or From Builder

```php
$container = new Container(new ContainerBuilder([
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
            'allow_override' => false
        ],
    ],
    'nested' => [
        [
            'namespace' => 'files',
            'services'  => [
                new defaultService(['name' => 'directory'
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
$container = new Container(new ContainerBuilder([
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
$container = new Container(new ContainerBuilder([
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

## Invoke Services With Service Options

we can build any container service with some options
 these options must implemented in iCService class interface
 an example of usage is on FunctorService.

```php
$container->set(new FunctorService([
   'name'     => 'service_name',
   'callable' => function($arg1, $arg2) {
       # callable function will bind to service object as closure method
       # so you can access methods from FunctorService
       $sc = $this->getServiceContainer();

       # here we return service result
       return $arg1.' '.$arg2;
   },
   'allow_override'   => false
]));

$container->fresh('service_name', [$arg1Val, $arg2Val]);
```

## Understand Refresh Service Retrieve

```php
$services->set(new FunctorService('dynamicUri', function($arg = null) {
    return sprintf(
        '%s Service Requested. <br/>'
        , date('H:i:s'), $arg
    );
}));

echo $services->get('dynamicUri');
sleep(2);
echo $services->get('dynamicUri');
sleep(2);
echo $services->fresh('dynamicUri');
sleep(2);
echo $services->get('dynamicUri');
sleep(2);
echo $services->get('dynamicUri', ['arg' => 'this is new request because options changed.']);
```

result:
```
12:19:49 Service Requested. 
12:19:49 Service Requested. 
12:19:53 Service Requested. // fresh request
12:19:49 Service Requested. 
12:19:57 Service Requested. // with new options consume as fresh
```