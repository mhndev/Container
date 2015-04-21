<?php
namespace Poirot\Container\Interfaces;

use Poirot\Core\Interfaces\iPoirotOptions;

interface iCService extends iPoirotOptions
{
    /**
     * Set Service Name
     *
     * @param string $name Service Name
     *
     * @return $this
     */
    function setName($name);

    /**
     * Get Service Name
     *
     * @return string
     */
    function getName();

    /**
     * Create Service
     *
     * @return mixed
     */
    function createService();

    /**
     * Set flag indicating whether service refreshed
     * on new instance request
     *
     * @param boolean $flag
     *
     * @return $this
     */
    function setRefreshRetrieve($flag);

    /**
     * Get flag on indicating whether service refreshed
     * on new instance request?
     *
     * @return boolean
     */
    function getRefreshRetrieve();

    /**
     * Set Allow Override By Service
     *
     * @param boolean $allow Flag
     *
     * @return $this
     */
    function setAllowOverride($allow);

    /**
     * Get allow override
     *
     * @return boolean
     */
    function getAllowOverride();
}
