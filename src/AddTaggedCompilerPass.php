<?php

namespace Paysera\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class AddTaggedCompilerPass implements CompilerPassInterface
{
    private $parentServiceId;
    private $tagName;
    private $methodName;
    private $arguments;

    /**
     * @param string $parentServiceId
     * @param string $tagName
     * @param string $methodName
     * @param array $arguments
     */
    public function __construct($parentServiceId, $tagName, $methodName, array $arguments = array())
    {
        $this->parentServiceId = $parentServiceId;
        $this->tagName = $tagName;
        $this->methodName = $methodName;
        $this->arguments = $arguments;
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->parentServiceId)) {
            throw new InvalidConfigurationException('No such service: ' . $this->parentServiceId);
        }

        $definition = $container->getDefinition($this->parentServiceId);
        $services = $container->findTaggedServiceIds($this->tagName);
        foreach ($services as $id => $tags) {
            $this->validateOptionalArguments($tags);
            foreach ($tags as $tag) {
                $parameters = array(new Reference($id));
                foreach ($this->arguments as $argumentName) {
                    foreach ($tag as $attributeName => $attributeValue) {
                        if ($attributeName === $argumentName) {
                            $parameters[] = $attributeValue;
                            break;
                        }
                    }
                }
                $definition->addMethodCall($this->methodName, $parameters);
            }
        }
    }

    /**
     * @see \Paysera\Component\DependencyInjection\TaggedCompilerPassTest::test_optionalArguments
     * @param array $tags
     */
    private function validateOptionalArguments(array $tags)
    {
        foreach ($tags as $tag) {
            $onlyOptional = false;
            foreach ($this->arguments as $argumentName) {
                $foundAttribute = false;
                foreach ($tag as $attributeName => $attributeValue) {
                    if ($attributeName === $argumentName) {
                        $foundAttribute = true;
                        break;
                    }
                }
                if ($onlyOptional && $foundAttribute) {
                    throw new InvalidConfigurationException(sprintf(
                        'Some required attributes are missing in service %s tag %s definition',
                        $this->parentServiceId,
                        $this->tagName
                    ));
                }
                if (!$foundAttribute) {
                    $onlyOptional = true; // the rest of the arguments must not be given
                }
            }
        }
    }
}
