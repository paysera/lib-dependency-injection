<?php

declare(strict_types=1);

namespace Paysera\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class DefinitionsConfigurator implements ConfiguratorInterface
{
    private $definitions;

    public function __construct($definitions)
    {
        $this->definitions = $definitions;
    }

    public function load(ContainerBuilder $container)
    {
        $container->addDefinitions($this->definitions);
    }
}
