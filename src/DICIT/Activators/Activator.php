<?php

namespace DICIT\Activators;

use DICIT\Container;

interface Activator
{
    /**
     *
     * @param Container $container
     * @param string $serviceName
     * @param array $serviceConfig
     */
    public function createInstance(Container $container, $serviceName, array $serviceConfig);

    /**
     * @param array $serviceConfig
     * @return mixed
     */
    public function canActivate(array $serviceConfig);
}