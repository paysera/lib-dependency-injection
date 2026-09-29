<?php
declare(strict_types=1);

namespace Paysera\Component\DependencyInjection\Tests\Unit\Mocks;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MockParameterPass implements CompilerPassInterface
{
    private $parameterName;

    public function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    public function process(ContainerBuilder $container)
    {
        $container->setParameter($this->parameterName, true);
    }
}
