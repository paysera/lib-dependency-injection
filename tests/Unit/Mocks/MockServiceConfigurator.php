<?php
declare(strict_types=1);

namespace Paysera\Component\DependencyInjection\Tests\Unit\Mocks;

use Paysera\Component\DependencyInjection\ConfiguratorInterface;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MockServiceConfigurator implements ConfiguratorInterface
{
    private $serviceId;

    public function __construct(string $serviceId)
    {
        $this->serviceId = $serviceId;
    }

    public function load(ContainerBuilder $container)
    {
        $container->setDefinition($this->serviceId, (new Definition(stdClass::class))->setPublic(true));
    }
}
