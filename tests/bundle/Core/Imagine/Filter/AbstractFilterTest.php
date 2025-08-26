<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\Filter;

use Ibexa\Bundle\Core\Imagine\Filter\AbstractFilter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractFilterTest extends TestCase
{
    protected AbstractFilter & MockObject $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = $this->getFilter();
    }

    protected function getFilter(): AbstractFilter & MockObject
    {
        return $this->getMockForAbstractClass(AbstractFilter::class);
    }

    public function testGetSetOptions(): void
    {
        self::assertSame([], $this->filter->getOptions());
        $options = ['foo' => 'bar', 'some' => ['thing']];
        $this->filter->setOptions($options);
        self::assertSame($options, $this->filter->getOptions());
    }

    /**
     * @dataProvider getSetOptionNoDefaulValueProvider
     */
    public function testGetSetOptionNoDefaultValue(string $optionName, mixed $value): void
    {
        self::assertFalse($this->filter->hasOption($optionName));
        self::assertNull($this->filter->getOption($optionName));
        $this->filter->setOption($optionName, $value);
        self::assertTrue($this->filter->hasOption($optionName));
        self::assertSame($value, $this->filter->getOption($optionName));
    }

    /**
     * @return array<array{string, mixed}>
     */
    public static function getSetOptionNoDefaulValueProvider(): array
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
    public function testGetSetOptionWithDefaultValue(string $optionName, mixed $value, mixed $defaultValue): void
    {
        self::assertFalse($this->filter->hasOption($optionName));
        self::assertSame($defaultValue, $this->filter->getOption($optionName, $defaultValue));
        $this->filter->setOption($optionName, $value);
        self::assertTrue($this->filter->hasOption($optionName));
        self::assertSame($value, $this->filter->getOption($optionName));
    }

    /**
     * @return array<array{string, mixed, mixed}>
     */
    public static function getSetOptionWithDefaulValueProvider(): array
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
