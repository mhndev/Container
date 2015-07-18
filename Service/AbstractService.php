<?php
namespace Poirot\Container\Service;

use Poirot\Container\Interfaces\iContainer;
use Poirot\Container\Interfaces\iCService;
use Poirot\Container\Interfaces\Respec\iCServiceAware;
use Poirot\Container\Interfaces\Respec\iCServiceProvider;
use Poirot\Core\AbstractOptions;

/**
 * Note: Services Are Initialized On Container Manager
 *       Before Call Creation of Service,
 *       So Like The Created Services All Initializer Also
 *       Work On Service Objects.
 *
 */
abstract class AbstractService extends AbstractOptions
    implements
    iCService,
    iCServiceAware,
    iCServiceProvider
{
    /**
     * @var array
     * @see Container::initializer
     * @see Container::get
     */
    public $invoke_options;

    /**
     * @var string Service Name
     */
    protected $name;

    /**
     * Indicate to retrieve refresh instance
     * on creating service
     *
     * @var bool
     */
    protected $refreshInstance = false;

    /**
     * Indicate to allow overriding service
     * with another service
     *
     * @var boolean
     */
    protected $allowOverride = true;

    /**
     * implement iCServiceAware
     * @var iContainer Injected Container
     */
    protected $sc;

    /**
     * Create Service
     *
     * @return mixed
     */
    abstract function createService();

    /**
     * Set Service Name
     *
     * @param string $name Service Name
     *
     * @return $this
     */
    function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * Get Service Name
     *
     * @return string
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * Set flag indicating whether service refreshed
     * on new instance request
     *
     * @param boolean $flag
     *
     * @return $this
     */
    function setRefreshRetrieve($flag)
    {
        $this->refreshInstance = (boolean) $flag;

        return $this;
    }

    /**
     * Get flag on indicating whether service refreshed
     * on new instance request?
     *
     * @return boolean
     */
    function getRefreshRetrieve()
    {
        return $this->refreshInstance;
    }

    /**
     * Set Allow Override By Service
     *
     * @param boolean $allow Flag
     *
     * @return $this
     */
    function setAllowOverride($allow)
    {
        $this->allowOverride = (boolean) $allow;

        return $this;
    }

    /**
     * Get allow override
     *
     * @return boolean
     */
    function getAllowOverride()
    {
        return $this->allowOverride;
    }

    // Implement iCServiceAware:

    /**
     * Set Service Container
     *
     * @param iContainer $container
     */
    function setServiceContainer(iContainer $container)
    {
        $this->sc = $container;
    }

    /**
     * Get Service Container
     *
     * @return iContainer
     */
    function services()
    {
        return $this->sc;
    }
}
 