<?php

declare(strict_types=1);

namespace Paysera\Component\DependencyInjection\Tests\Unit;

use InvalidArgumentException;
use Paysera\Component\DependencyInjection\CompositeConfigurator;
use Paysera\Component\DependencyInjection\ConfiguratorLoader;
use Paysera\Component\DependencyInjection\DefinitionsConfigurator;
use Paysera\Component\DependencyInjection\Tests\Unit\Mocks\MockPassProviderConfigurator;
use Paysera\Component\DependencyInjection\Tests\Unit\Mocks\MockServiceConfigurator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

class ConfiguratorLoaderTest extends TestCase
{
    public function testCreateContainerLoadsTheConfiguratorAppliesItsPassesAddsParametersAndCompiles()
    {
        $container = ConfiguratorLoader::createContainer(
            new CompositeConfigurator([
                new MockServiceConfigurator('service.a'),
                new MockPassProviderConfigurator('pass.ran'),
            ]),
            ['answer' => 42]
        );

        $this->assertInstanceOf(FrozenParameterBag::class, $container->getParameterBag());
        $this->assertInstanceOf(stdClass::class, $container->get('service.a'));
        $this->assertTrue($container->getParameter('pass.ran'));
        $this->assertSame(42, $container->getParameter('answer'));
    }

    public function testCreateContainerAcceptsAConfiguratorThatProvidesNoCompilerPasses()
    {
        $container = ConfiguratorLoader::createContainer(new MockServiceConfigurator('service.a'));

        $this->assertInstanceOf(FrozenParameterBag::class, $container->getParameterBag());
        $this->assertInstanceOf(stdClass::class, $container->get('service.a'));
    }

    public function testLoadTracksTheConfiguratorAsAContainerResource()
    {
        $container = new ContainerBuilder();

        (new ConfiguratorLoader($container))->load(new DefinitionsConfigurator([]));

        $paths = array_map('strval', $container->getResources());
        $this->assertContains(realpath((new ReflectionClass(DefinitionsConfigurator::class))->getFileName()), $paths);
    }

    public function testLoadLoadsTheConfiguratorIntoTheContainerAndReturnsTheContainer()
    {
        $container = new ContainerBuilder();
        $loader = new ConfiguratorLoader($container);

        $this->assertSame($container, $loader->load(new MockServiceConfigurator('service.a')));
        $this->assertTrue($container->hasDefinition('service.a'));
    }

    public function testSupportsOnlyConfigurators()
    {
        $loader = new ConfiguratorLoader(new ContainerBuilder());

        $this->assertTrue($loader->supports(new MockServiceConfigurator('service.a')));
        $this->assertFalse($loader->supports(new stdClass()));
        $this->assertFalse($loader->supports('a-string'));
    }

    public function testLoadRejectsAnythingThatIsNotAConfigurator()
    {
        $loader = new ConfiguratorLoader(new ContainerBuilder());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource must be configurator');
        $loader->load(new stdClass());
    }
}
