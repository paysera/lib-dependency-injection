<?php

namespace Paysera\Component\DependencyInjection;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfiguratorLoader extends Loader
{
    private $container;

    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
    }

    /**
     * @param ConfiguratorInterface $resource
     * @param string $type
     *
     * @throws \InvalidArgumentException
     */
    public function load($resource, $type = null)
    {
        if (!$resource instanceof ConfiguratorInterface) {
            throw new \InvalidArgumentException('Resource must be configurator');
        }

        $this->container->addObjectResource($resource);
        $resource->load($this->container);
    }

    /**
     * @param mixed $resource
     * @param string $type
     *
     * @return bool
     */
    public function supports($resource, $type = null)
    {
        return is_object($resource) && $resource instanceof ConfiguratorInterface;
    }

    public static function createContainer(ConfiguratorInterface $configurator, $parameters = array())
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
}
