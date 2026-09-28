<?php
declare(strict_types=1);

namespace Paysera\Component\DependencyInjection\Tests\Unit\Mocks;

use Paysera\Component\DependencyInjection\CompilerPassProviderInterface;
use Paysera\Component\DependencyInjection\ConfiguratorInterface;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MockPassProviderConfigurator implements ConfiguratorInterface, CompilerPassProviderInterface
{
    private $pass;

    public function __construct(string $parameterName)
    {
        $this->pass = new MockParameterPass($parameterName);
    }

    public function load(ContainerBuilder $container)
    {
        $container->setDefinition('service.provider', new Definition(stdClass::class));
    }

    public function getCompilerPasses(): array
    {
        return [$this->pass];
    }
}
