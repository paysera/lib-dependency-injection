<?php

declare(strict_types=1);

namespace Paysera\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class AddTaggedCompilerPass implements CompilerPassInterface
{
    /**
     * Calls method passing tagged service
     */
    const CALL_MODE_SERVICE = 'service';

    /**
     * Calls method passing tagged service, but also marks tagged services as lazy
     */
    const CALL_MODE_LAZY_SERVICE = 'lazy_service';

    /**
     * Calls method passing only tagged service ID. Makes tagged services public
     */
    const CALL_MODE_ID = 'id';

    private $collectorServiceId;
    private $tagName;
    private $methodName;
    private $parameters;
    private $callMode;

    /**
     * @var string|null
     */
    private $priorityAttribute;

    public function __construct(string $collectorServiceId, string $tagName, string $methodName, array $parameters = [])
    {
        $this->collectorServiceId = $collectorServiceId;
        $this->tagName = $tagName;
        $this->methodName = $methodName;
        $this->parameters = $parameters;
        $this->callMode = self::CALL_MODE_SERVICE;
    }

    /**
     * If enabled, tags will be ordered by priority before initiating method calls.
     * Lower priority means called earlier.
     * If no priority provided, defaults to 0.
     *
     * @param string $priorityAttribute
     * @return $this
     */
    public function enablePriority(string $priorityAttribute = 'priority'): self
    {
        $this->priorityAttribute = $priorityAttribute;
        return $this;
    }

    /**
     * Sets call mode to one of CALL_MODE_* constants
     *
     * @param string $callMode
     * @return $this
     */
    public function setCallMode(string $callMode): self
    {
        $this->callMode = $callMode;
        return $this;
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->collectorServiceId)) {
            throw new InvalidConfigurationException('No such service: ' . $this->collectorServiceId);
        }

        $definition = $container->getDefinition($this->collectorServiceId);
        $tags = $this->collectTags($container->findTaggedServiceIds($this->tagName));
        foreach ($tags as $tag) {
            $arguments = array_merge(
                [$this->getServiceArgument($container, $tag['service_id'])],
                $this->collectAdditionalArguments($tag['attributes'], $tag['service_id'])
            );
            $definition->addMethodCall($this->methodName, $arguments);
        }
    }

    private function collectTags(array $tagsByServiceId)
    {
        $tags = [];
        foreach ($tagsByServiceId as $serviceId => $tagsInsideService) {
            foreach ($tagsInsideService as $tagAttributes) {
                $tags[] = [
                    'service_id' => $serviceId,
                    'attributes' => $tagAttributes,
                ];
            }
        }

        return $this->prioritizeTags($tags);
    }

    private function prioritizeTags(array $tags)
    {
        if ($this->priorityAttribute === null) {
            return $tags;
        }

        usort($tags, function (array $tag1, array $tag2) {
            $tag1Priority = $tag1['attributes'][$this->priorityAttribute] ?? 0;
            $tag2Priority = $tag2['attributes'][$this->priorityAttribute] ?? 0;
            return $tag1Priority - $tag2Priority;
        });

        return $tags;
    }

    /**
     * Can be overwritten in extended classes
     *
     * @param ContainerBuilder $container
     * @param string $id
     * @return mixed returns argument to pass to the collector service
     */
    private function getServiceArgument(ContainerBuilder $container, string $id)
    {
        if ($this->callMode === self::CALL_MODE_ID) {
            $container->getDefinition($id)->setPublic(true);

            return $id;
        }

        if ($this->callMode === self::CALL_MODE_LAZY_SERVICE) {
            $container->getDefinition($id)->setLazy(true);
        }

        return new Reference($id);
    }

    private function collectAdditionalArguments(array $tagAttributes, string $serviceId): array
    {
        $onlyOptional = false;
        $arguments = [];
        foreach ($this->parameters as $key => $value) {
            if (is_numeric($key)) {
                $name = $value;
                $hasDefault = false;
                $default = null;
            } else {
                $name = $key;
                $hasDefault = true;
                $default = $value;
            }

            $hasAttribute = isset($tagAttributes[$name]);
            if ($hasAttribute && $onlyOptional) {
                throw new InvalidConfigurationException(sprintf(
                    'Some required attributes are missing in service %s tag %s definition',
                    $serviceId,
                    $this->tagName
                ));
            }

            if (!$hasAttribute && !$hasDefault) {
                $onlyOptional = true;
            } else {
                $arguments[] = $tagAttributes[$name] ?? $default;
            }
        }
        return $arguments;
    }
}
