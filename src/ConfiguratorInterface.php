<?php

declare(strict_types=1);

namespace Paysera\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
interface ConfiguratorInterface
{
    public function load(ContainerBuilder $container);
}
