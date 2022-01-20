<?php

declare(strict_types=1);

namespace Paysera\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CompositeConfigurator implements ConfiguratorInterface, CompilerPassProviderInterface
{
    private $configurators;

    /**
     * @param ConfiguratorInterface[] $configurators
     */
    public function __construct(array $configurators = [])
    {
        foreach ($configurators as $configurator) {
            $this->registerConfigurator($configurator);
        }
    }

    public function registerConfigurator(ConfiguratorInterface $configurator)
    {
        $this->configurators[] = $configurator;
    }

    public function load(ContainerBuilder $container)
    {
        $loader = new ConfiguratorLoader($container);
        foreach ($this->configurators as $configurator) {
            $loader->load($configurator);
        }
    }

    /**
     * @return CompilerPassInterface[]
     */
    public function getCompilerPasses()
    {
        $passes = [];
        foreach ($this->configurators as $configurator) {
            if ($configurator instanceof CompilerPassProviderInterface) {
                $passes = array_merge($passes, $configurator->getCompilerPasses());
            }
        }
        return $passes;
    }
}
