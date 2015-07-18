<?php
namespace Poirot\Container\Interfaces\Respec;

use Poirot\Container\Interfaces\iContainer;

interface iCServiceProvider
{
    /**
     * Services Container
     *
     * @return iContainer
     */
    function services();
}
