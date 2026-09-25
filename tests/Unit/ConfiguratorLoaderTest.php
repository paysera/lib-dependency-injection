<?php

declare(strict_types=1);

namespace Paysera\Component\DependencyInjection\Tests\Unit;

use InvalidArgumentException;
use Paysera\Component\DependencyInjection\CompilerPassProviderInterface;
use Paysera\Component\DependencyInjection\CompositeConfigurator;
use Paysera\Component\DependencyInjection\ConfiguratorInterface;
use Paysera\Component\DependencyInjection\ConfiguratorLoader;
use Paysera\Component\DependencyInjection\DefinitionsConfigurator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

class ConfiguratorLoaderTest extends TestCase
{
    public function testCreateContainerLoadsTheConfiguratorAppliesItsPassesAddsParametersAndCompiles()
    {
        $container = ConfiguratorLoader::createContainer(
            new CompositeConfigurator([
                $this->createConfigurator('service.a'),
                $this->createPassProvider('pass.ran'),
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
        $container = ConfiguratorLoader::createContainer($this->createConfigurator('service.a'));

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

        $this->assertSame($container, $loader->load($this->createConfigurator('service.a')));
        $this->assertTrue($container->hasDefinition('service.a'));
    }

    public function testSupportsOnlyConfigurators()
    {
        $loader = new ConfiguratorLoader(new ContainerBuilder());

        $this->assertTrue($loader->supports($this->createConfigurator('service.a')));
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

    public function testCompositeConfiguratorLoadsInOrderAndCollectsPassesOnlyFromProviders()
    {
        $plain = $this->createConfigurator('service.plain');
        $provider = $this->createPassProvider('pass.ran');
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

    private function createConfigurator(string $serviceId): ConfiguratorInterface
    {
        return new class($serviceId) implements ConfiguratorInterface {
            private $serviceId;

            public function __construct(string $serviceId)
            {
                $this->serviceId = $serviceId;
            }

            public function load(ContainerBuilder $container)
            {
                $container->setDefinition($this->serviceId, (new Definition(stdClass::class))->setPublic(true));
            }
        };
    }

    /**
     * @return ConfiguratorInterface&CompilerPassProviderInterface
     */
    private function createPassProvider(string $parameterName): ConfiguratorInterface
    {
        $pass = new class($parameterName) implements CompilerPassInterface {
            private $parameterName;

            public function __construct(string $parameterName)
            {
                $this->parameterName = $parameterName;
            }

            public function process(ContainerBuilder $container)
            {
                $container->setParameter($this->parameterName, true);
            }
        };

        return new class($pass) implements ConfiguratorInterface, CompilerPassProviderInterface {
            private $pass;

            public function __construct(CompilerPassInterface $pass)
            {
                $this->pass = $pass;
            }

            public function load(ContainerBuilder $container)
            {
                $container->setDefinition('service.provider', new Definition(stdClass::class));
            }

            public function getCompilerPasses(): array
            {
                return [$this->pass];
            }
        };
    }
}
