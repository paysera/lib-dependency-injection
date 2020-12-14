<?php

declare(strict_types=1);

namespace Paysera\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * @internal
 */
interface CompilerPassProviderInterface
{
    /**
     * @return CompilerPassInterface[]
     */
    public function getCompilerPasses(): array;
}
