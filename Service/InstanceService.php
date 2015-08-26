<?php
namespace Poirot\Container\Service;

class InstanceService extends AbstractService
{
    protected $service;

    /**
     * Create Service
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
 