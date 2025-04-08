<?php

namespace Orb\Trait;

use Psr\Container\ContainerInterface;

trait ContainerAwareTrait
{

    protected ContainerInterface $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }
}
