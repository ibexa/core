<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter;

use Ibexa\Bundle\Core\Imagine\Filter\AbstractFilter;
use PHPUnit\Framework\TestCase;

class AbstractFilterTest extends TestCase
{
    /** @var \Ibexa\Bundle\Core\Imagine\Filter\AbstractFilter */
    protected $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = $this->getFilter();
    }

    protected function getFilter()
    {
        return $this->getMockForAbstractClass(AbstractFilter::class);
    }

    public function testGetSetOptions()
    {
        self::assertSame([], $this->filter->getOptions());
        $options = ['foo' => 'bar', 'some' => ['thing']];
        $this->filter->setOptions($options);
        self::assertSame($options, $this->filter->getOptions());
    }

    /**
     * @dataProvider getSetOptionNoDefaulValueProvider
     */
    public function testGetSetOptionNoDefaultValue($optionName, $value)
    {
        self::assertFalse($this->filter->hasOption($optionName));
        self::assertNull($this->filter->getOption($optionName));
        $this->filter->setOption($optionName, $value);
        self::assertTrue($this->filter->hasOption($optionName));
        self::assertSame($value, $this->filter->getOption($optionName));
    }

    public function getSetOptionNoDefaulValueProvider()
    {
        return [
            ['foo', 'bar'],
            ['foo', '123'],
            ['bar', 123],
            ['bar', ['foo', 123]],
            ['bool', true],
            ['obj', new \stdClass()],
        ];
    }

    /**
     * @dataProvider getSetOptionWithDefaulValueProvider
     */
    public function testGetSetOptionWithDefaultValue($optionName, $value, $defaultValue)
    {
        self::assertFalse($this->filter->hasOption($optionName));
        self::assertSame($defaultValue, $this->filter->getOption($optionName, $defaultValue));
        $this->filter->setOption($optionName, $value);
        self::assertTrue($this->filter->hasOption($optionName));
        self::assertSame($value, $this->filter->getOption($optionName));
    }

    public function getSetOptionWithDefaulValueProvider()
    {
        return [
            ['foo', 'bar', 'default'],
            ['foo', '123', 'default2'],
            ['bar', 123, 0],
            ['bar', ['foo', 123], []],
            ['bool', true, false],
            ['obj', new \stdClass(), new \stdClass()],
        ];
    }
}
