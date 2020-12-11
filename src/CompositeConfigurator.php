<?php

declare(strict_types=1);

namespace Paysera\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
class CompositeConfigurator implements ConfiguratorInterface, CompilerPassProviderInterface
{
    private $configurators = [];

    /**
     * @param ConfiguratorInterface[] $configurators
     */
    public function __construct(array $configurators = [])
    {
        foreach ($configurators as $configurator) {
            $this->registerConfigurator($configurator);
        }
    }

    public function registerConfigurator(ConfiguratorInterface $configurator): void
    {
        $this->configurators[] = $configurator;
    }

    public function load(ContainerBuilder $container): void
    {
        $loader = new ConfiguratorLoader($container);
        foreach ($this->configurators as $configurator) {
            $loader->load($configurator);
        }
    }

    /**
     * @return CompilerPassInterface[]
     */
    public function getCompilerPasses(): array
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
