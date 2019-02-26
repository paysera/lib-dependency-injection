<?php
declare(strict_types=1);

namespace Paysera\Component\DependencyInjection\Tests\Unit\Mocks;

class MockFruitCollector
{
    const DEFAULT_COLOR = 'yellow';

    /**
     * @var mixed[]
     */
    protected $fruit = [];

    /**
     * @var array
     */
    protected $colors = [];

    /**
     * @param mixed $fruit
     * @param string $key
     * @param string $color optional argument to test optional tag attributes
     */
    public function addFruit($fruit, $key, $color = self::DEFAULT_COLOR)
    {
        $this->fruit[$key] = $fruit;
        $this->colors[$key] = $color;
    }

    /**
     * @param string $key
     * @return mixed|null
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
