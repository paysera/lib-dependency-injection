<?php

declare(strict_types=1);

namespace Paysera\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

interface CompilerPassProviderInterface
{
    /**
     * @return CompilerPassInterface[]
     */
    public function getCompilerPasses();
}
