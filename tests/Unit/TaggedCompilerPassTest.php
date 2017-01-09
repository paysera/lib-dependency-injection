<?php

namespace Paysera\Component\DependencyInjection;

use Paysera\Component\DependencyInjection\Tests\Unit\Mocks\MockFruitCollector;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class TaggedCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ORANGE_CLASS = '\Paysera\Component\DependencyInjection\Tests\Unit\Mocks\MockOrange';
    const TEST_PEAR_CLASS = '\Paysera\Component\DependencyInjection\Tests\Unit\Mocks\MockPear';
    const TEST_COLLECTOR_CLASS = '\Paysera\Component\DependencyInjection\Tests\Unit\Mocks\MockFruitCollector';
    const INVALID_CONFIGURATION_EXCEPTION_CLASS =
        '\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException';

    public function test()
    {
        $tagName = 'fruit';

        $container = new ContainerBuilder();
        $container->setDefinition('collector', new Definition(self::TEST_COLLECTOR_CLASS));

        // Orange
        $orange = new Definition(self::TEST_ORANGE_CLASS);
        $orange->addTag($tagName, array('key' => 'orange'));
        $container->setDefinition('evp.orange', $orange);

        // Pear, the same class tagged as two different fruit
        $pear = new Definition(self::TEST_PEAR_CLASS);
        $pear->addTag($tagName, array('key' => 'pear'));
        $pear->addTag($tagName, array('key' => 'apple'));
        $container->setDefinition('evp.pear', $pear);

        $compilerPass = new AddTaggedCompilerPass('collector', $tagName, 'addFruit', array('key'));
        $compilerPass->process($container);

        /** @var MockFruitCollector $collector */
        $collector = $container->get('collector');
        $this->assertNotNull($collector->getFruit('orange'));
        $this->assertInstanceOf(self::TEST_ORANGE_CLASS, $collector->getFruit('orange'));

        $this->assertNotNull($collector->getFruit('pear'));
        $this->assertInstanceOf(self::TEST_PEAR_CLASS, $collector->getFruit('pear'));

        $this->assertNotNull($collector->getFruit('apple'));
        $this->assertInstanceOf(self::TEST_PEAR_CLASS, $collector->getFruit('apple'));
    }

    /**
     * A tag can omit right-hand (optional) arguments to the adder method.
     *
     * @dataProvider provider_optionalArguments
     *
     * @param array $tagAttributes
     * @param bool $isError
     * @param string|null $expectedColor
     */
    public function test_optionalArguments(array $tagAttributes, $isError, $expectedColor = null)
    {
        $container = new ContainerBuilder();
        $container->setDefinition('collector', new Definition(self::TEST_COLLECTOR_CLASS));

        // Foo provider
        $pear = new Definition(self::TEST_PEAR_CLASS);
        $pear->addTag('fruit', $tagAttributes);
        $container->setDefinition('evp.pear', $pear);

        $compilerPass = new AddTaggedCompilerPass('collector', 'fruit', 'addFruit', array('key', 'color'));
        if ($isError) {
            $this->setExpectedException(self::INVALID_CONFIGURATION_EXCEPTION_CLASS);
        }
        $compilerPass->process($container);

        if ($expectedColor !== null) {
            /** @var MockFruitCollector $collector */
            $collector = $container->get('collector');
            $this->assertEquals($expectedColor, $collector->getColor($tagAttributes['key']));
        }
    }

    public function provider_optionalArguments()
    {
        return array(
            'Forbids skipping a non-optional argument.' => array(
                array('color' => 'green'), // color is the second optional argument
                true,
            ),

            'Sets an optional parameter' => array(
                array('key' => 'foo', 'color' => 'light-green'),
                false,
                'light-green',
            ),

            'Does not override a default parameter with anything,'
            . ' if it is omitted in the tag' => array(
                array('key' => 'foo'),
                false,
                MockFruitCollector::DEFAULT_COLOR,
            ),
        );
    }
}
