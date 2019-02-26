<?php
declare(strict_types=1);

namespace Paysera\Component\DependencyInjection\Tests\Unit;

use Paysera\Component\DependencyInjection\AddTaggedCompilerPass;
use Paysera\Component\DependencyInjection\Tests\Unit\Mocks\MockFruitCollector;
use Paysera\Component\DependencyInjection\Tests\Unit\Mocks\MockOrange;
use Paysera\Component\DependencyInjection\Tests\Unit\Mocks\MockPear;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AddTaggedCompilerPassTest extends TestCase
{
    /**
     * @dataProvider provider
     * @param array|null $expectations
     * @param AddTaggedCompilerPass $compilerPass
     * @param array $definitions
     */
    public function testProcess(
        $expectations,
        AddTaggedCompilerPass $compilerPass,
        array $definitions,
        array $expectedDefinitions = null
    ) {
        $container = new ContainerBuilder();
        $container->setDefinition('collector', new Definition(MockFruitCollector::class));
        
        foreach ($definitions as $id => $definition) {
            $container->setDefinition($id, $definition);
        }
        
        if ($expectations === null) {
            $this->expectException(InvalidConfigurationException::class);
            $compilerPass->process($container);
            return;
        }
        
        $compilerPass->process($container);
        
        /** @var MockFruitCollector $collector */
        $collector = $container->get('collector');
        foreach ($expectations as $key => $expectation) {
            if (class_exists($expectation[0])) {
                $this->assertInstanceOf($expectation[0], $collector->getFruit($key));
            } else {
                $this->assertSame($expectation[0], $collector->getFruit($key));
            }
            $this->assertSame($expectation[1], $collector->getColor($key));
        }
        
        if ($expectedDefinitions === null) {
            return;
        }
        
        foreach ($expectedDefinitions as $id => $expectedDefinition) {
            $this->assertEquals($expectedDefinition, $container->getDefinition($id));
        }
    }
    
    public function provider()
    {
        return [
            'Works with simple case' => [
                [
                    'orange' => [MockOrange::class, MockFruitCollector::DEFAULT_COLOR],
                ],
                new AddTaggedCompilerPass(
                    'collector',
                    'fruit',
                    'addFruit',
                    ['key']
                ),
                [
                    'orange' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange'])
                    ,
                ],
            ],
            
            'Works with optional parameters' => [
                [
                    'orange' => [MockOrange::class, MockFruitCollector::DEFAULT_COLOR],
                    'pear' => [MockPear::class, 'pearish'],
                ],
                new AddTaggedCompilerPass(
                    'collector',
                    'fruit',
                    'addFruit',
                    ['key', 'color']
                ),
                [
                    'orange' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange'])
                    ,
                    'pear' => (new Definition(MockPear::class))
                        ->addTag('fruit', ['key' => 'pear', 'color' => 'pearish'])
                    ,
                ],
            ],

            'Works with multiple services and tags' => [
                [
                    'orange' => [MockOrange::class, MockFruitCollector::DEFAULT_COLOR],
                    'pear' => [MockPear::class, MockFruitCollector::DEFAULT_COLOR],
                    'black_pear' => [MockPear::class, 'black'],
                ],
                new AddTaggedCompilerPass(
                    'collector',
                    'fruit',
                    'addFruit',
                    ['key', 'color']
                ),
                [
                    'orange' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange'])
                    ,
                    'pear' => (new Definition(MockPear::class))
                        ->addTag('fruit', ['key' => 'pear'])
                        ->addTag('fruit', ['key' => 'black_pear', 'color' => 'black'])
                    ,
                ],
            ],

            'Forbids skipping a non-optional argument' => [
                null,
                new AddTaggedCompilerPass(
                    'collector',
                    'fruit',
                    'addFruit',
                    ['key', 'color']
                ),
                [
                    'orange' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['color' => 'orange'])
                    ,
                ],
            ],

            'Works with defaulted attributes' => [
                [
                    'default' => [MockOrange::class, 'orange'],
                    'pear' => [MockPear::class, MockFruitCollector::DEFAULT_COLOR],
                ],
                new AddTaggedCompilerPass(
                    'collector',
                    'fruit',
                    'addFruit',
                    ['key' => 'default', 'color']
                ),
                [
                    's1' => (new Definition(MockOrange::class))
                        ->addTag('fruit', [])
                    ,
                    's2' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['color' => 'orange'])
                    ,
                    's3' => (new Definition(MockPear::class))
                        ->addTag('fruit', ['key' => 'pear'])
                    ,
                ],
            ],

            'Overrides default for optional parameter' => [
                [
                    'orange' => [MockOrange::class, 'my default'],
                    'pear' => [MockPear::class, 'pear custom'],
                ],
                new AddTaggedCompilerPass(
                    'collector',
                    'fruit',
                    'addFruit',
                    ['key', 'color' => 'my default']
                ),
                [
                    's1' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange'])
                    ,
                    's2' => (new Definition(MockPear::class))
                        ->addTag('fruit', ['key' => 'pear', 'color' => 'pear custom'])
                    ,
                ],
            ],

            'Works with priorities' => [
                [
                    'orange' => [MockOrange::class, 'orange-p1'],
                    'pear' => [MockPear::class, 'pear-default'],
                ],
                (new AddTaggedCompilerPass(
                    'collector',
                    'fruit',
                    'addFruit',
                    ['key', 'color']
                ))->enablePriority(),
                [
                    's1' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange', 'priority' => 1, 'color' => 'orange-p1'])
                    ,
                    's2' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange', 'color' => 'orange-default'])
                    ,
                    's3' => (new Definition(MockPear::class))
                        ->addTag('fruit', ['key' => 'pear', 'priority' => -2, 'color' => 'pear-p-2'])
                    ,
                    's4' => (new Definition(MockPear::class))
                        ->addTag('fruit', ['key' => 'pear', 'color' => 'pear-default'])
                    ,
                    's5' => (new Definition(MockPear::class))
                        ->addTag('fruit', ['key' => 'pear', 'priority' => -1, 'color' => 'pear-p-1'])
                    ,
                ],
            ],

            'Takes priority attribute into account' => [
                [
                    'orange' => [MockOrange::class, 'orange-p1'],
                ],
                (new AddTaggedCompilerPass(
                    'collector',
                    'fruit',
                    'addFruit',
                    ['key', 'color']
                ))->enablePriority('p'),
                [
                    's1' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange', 'p' => 1, 'color' => 'orange-p1'])
                    ,
                    's2' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange', 'color' => 'orange-default'])
                    ,
                ],
            ],

            'Priorities allows multiple tags' => [
                [
                    'orange' => [MockOrange::class, 'orange-p3'],
                ],
                (new AddTaggedCompilerPass(
                    'collector',
                    'fruit',
                    'addFruit',
                    ['key', 'color']
                ))->enablePriority('p'),
                [
                    's1' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange', 'p' => 1, 'color' => 'orange-p1'])
                        ->addTag('fruit', ['key' => 'orange', 'p' => 3, 'color' => 'orange-p3'])
                        ->addTag('fruit', ['key' => 'orange', 'p' => -1, 'color' => 'orange-p-1'])
                    ,
                    's2' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange', 'color' => 'orange-default'])
                    ,
                ],
            ],

            'Returns IDs' => [
                [
                    'orange' => ['s1', MockFruitCollector::DEFAULT_COLOR],
                    'pear' => ['s2', 'black'],
                ],
                (new AddTaggedCompilerPass(
                    'collector',
                    'fruit',
                    'addFruit',
                    ['key', 'color']
                ))->setCallMode(AddTaggedCompilerPass::CALL_MODE_ID),
                [
                    's1' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange'])
                    ,
                    's2' => (new Definition(MockPear::class))
                        ->addTag('fruit', ['key' => 'pear',  'color' => 'black'])
                    ,
                ],
            ],

            'Works with priorities and IDs' => [
                [
                    'orange' => ['s1', MockFruitCollector::DEFAULT_COLOR],
                ],
                (new AddTaggedCompilerPass(
                    'collector',
                    'fruit',
                    'addFruit',
                    ['key', 'color']
                ))->setCallMode(AddTaggedCompilerPass::CALL_MODE_ID)->enablePriority(),
                [
                    's1' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange'])
                    ,
                    's2' => (new Definition(MockPear::class))
                        ->addTag('fruit', ['key' => 'orange',  'color' => 'black', 'priority' => -1])
                    ,
                ],
            ],

            'ID makes definitions public' => [
                [
                    'orange' => ['s1', MockFruitCollector::DEFAULT_COLOR],
                ],
                (new AddTaggedCompilerPass(
                    'collector',
                    'fruit',
                    'addFruit',
                    ['key']
                ))->setCallMode(AddTaggedCompilerPass::CALL_MODE_ID),
                [
                    's1' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange'])
                    ,
                ],
                [
                    's1' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange'])
                        ->setPublic(true)
                    ,
                ],
            ],

            'LAZY_SERVICES makes definitions lazy' => [
                [
                    'orange' => [MockOrange::class, MockFruitCollector::DEFAULT_COLOR],
                ],
                (new AddTaggedCompilerPass(
                    'collector',
                    'fruit',
                    'addFruit',
                    ['key']
                ))->setCallMode(AddTaggedCompilerPass::CALL_MODE_LAZY_SERVICE),
                [
                    's1' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange'])
                    ,
                ],
                [
                    's1' => (new Definition(MockOrange::class))
                        ->addTag('fruit', ['key' => 'orange'])
                        ->setLazy(true)
                    ,
                ],
            ],
        ];
    }
}
