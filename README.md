# Poirot\Container

Modern. Fast. Minimalism. Service Manager Container.

## Overview usage sample

```php
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

/** @var Directory $dir */
$dir = $container->get('sysdir')
    ->scanDir();
```
