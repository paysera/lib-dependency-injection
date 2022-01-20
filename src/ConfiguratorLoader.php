<?php

declare(strict_types=1);

namespace Paysera\Component\DependencyInjection;

use InvalidArgumentException;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfiguratorLoader extends Loader
{
    private $container;

    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
    }

    public static function createContainer(ConfiguratorInterface $configurator, $parameters = []): ContainerBuilder
    {
        $container = new ContainerBuilder();

        if ($configurator instanceof CompilerPassProviderInterface) {
            foreach ($configurator->getCompilerPasses() as $pass) {
                $container->addCompilerPass($pass);
            }
        }

        /** @var ConfiguratorLoader $loader */
        $loader = new static($container);
        $loader->load($configurator);

        $container->getParameterBag()->add($parameters);

        $container->compile();

        return $container;
    }

    /**
     * @param ConfiguratorInterface $resource
     * @param string|null $type
     *
     * @throws InvalidArgumentException
     */
    public function load($resource, $type = null)
    {
        if (!$resource instanceof ConfiguratorInterface) {
            throw new InvalidArgumentException('Resource must be configurator');
        }

        $this->container->addObjectResource($resource);
        $resource->load($this->container);
    }

    /**
     * @param mixed $resource
     * @param string|null $type
     *
     * @return bool
     */
    public function supports($resource, $type = null)
    {
        return is_object($resource) && $resource instanceof ConfiguratorInterface;
    }
}
