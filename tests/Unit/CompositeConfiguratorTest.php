<?php

declare(strict_types=1);

namespace Paysera\Component\DependencyInjection\Tests\Unit;

use Paysera\Component\DependencyInjection\CompositeConfigurator;
use Paysera\Component\DependencyInjection\Tests\Unit\Mocks\MockPassProviderConfigurator;
use Paysera\Component\DependencyInjection\Tests\Unit\Mocks\MockServiceConfigurator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CompositeConfiguratorTest extends TestCase
{
    public function testLoadsInOrderAndCollectsPassesOnlyFromProviders()
    {
        $plain = new MockServiceConfigurator('service.plain');
        $provider = new MockPassProviderConfigurator('pass.ran');
        $composite = new CompositeConfigurator([$plain]);
        $composite->registerConfigurator($provider);
        $container = new ContainerBuilder();

        $composite->load($container);

        $this->assertSame(
            ['service.plain', 'service.provider'],
            array_values(array_intersect(array_keys($container->getDefinitions()), ['service.plain', 'service.provider']))
        );
        $this->assertSame($provider->getCompilerPasses(), $composite->getCompilerPasses());
    }
}
