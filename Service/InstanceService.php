<?php
namespace Poirot\Container\Service;

class InstanceService extends AbstractService
{
    protected $service;

    /**
     * Construct
     *
     * also can used as:
     * - new InstanceService('name', $service);
     * or setter set
     * - new InstanceService([ 'service' => [..] ..options])
     *
     * @param array|callable $options
     * @param null|string    $service
     */
    function __construct($options = null, $service = null)
    {
        if (is_array($options)) {
            ## ['callable' => '..', ..]
            $this->from($options);
        }
        elseif (is_string($options)) {
            ## __construct('name', [$this, 'method'])
            $this->setName($options);
            $this->setService($service);
        }
    }

    /**
     * Create Service
     *
     * TODO $this->invoke_options as new instance constructor
     *
     * @return mixed
     */
    function createService()
    {
        if(is_string($this->service) && class_exists($this->service))
            $this->service = new $this->service;

        return $this->service;
    }

    /**
     * @param mixed $class
     */
    public function setService($class)
    {
        $this->service = $class;
    }
}
 