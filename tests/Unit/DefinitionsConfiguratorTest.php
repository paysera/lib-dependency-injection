<?php

declare(strict_types=1);

namespace Paysera\Component\DependencyInjection\Tests\Unit;

use Paysera\Component\DependencyInjection\DefinitionsConfigurator;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DefinitionsConfiguratorTest extends TestCase
{
    public function testLoadAddsTheDefinitionsToTheContainer()
    {
        $definition = new Definition(stdClass::class);
        $container = new ContainerBuilder();

        (new DefinitionsConfigurator(['service.a' => $definition]))->load($container);

        $this->assertSame($definition, $container->getDefinition('service.a'));
    }
}
