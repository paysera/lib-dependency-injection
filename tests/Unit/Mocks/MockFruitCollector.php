<?php

namespace Paysera\Component\DependencyInjection\Tests\Unit\Mocks;

class MockFruitCollector
{
    const DEFAULT_COLOR = 'yellow';

    /**
     * @var MockFruitInterface[]
     */
    protected $fruit = array();

    /**
     * @var array
     */
    protected $colors = array();

    /**
     * @param MockFruitInterface $fruit
     * @param string $key
     * @param string $color optional argument to test optional tag attributes
     */
    public function addFruit(MockFruitInterface $fruit, $key, $color = self::DEFAULT_COLOR)
    {
        $this->fruit[$key] = $fruit;
        $this->colors[$key] = $color;
    }

    /**
     * @param string $key
     * @return MockFruitInterface|null
     */
    public function getFruit($key)
    {
        return isset($this->fruit[$key]) ? $this->fruit[$key] : null;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getColor($key)
    {
        return isset($this->colors[$key]) ? $this->colors[$key] : null;
    }
}
