<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\Comparable;
use Ibexa\Contracts\Core\FieldType\FieldType;
use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Core\Persistence\TransformationProcessor;
use PHPUnit\Framework\MockObject\MockObject;

abstract class FieldTypeTestCase extends BaseFieldTypeTestCase
{
    private FieldType & Comparable $fieldTypeUnderTest;

    protected function getTransformationProcessorMock(): TransformationProcessor & MockObject
    {
        return $this->getMockForAbstractClass(
            TransformationProcessor::class,
            [],
            '',
            false,
            true,
            true,
            ['transform', 'transformByGroup']
        );
    }

    /**
     * @phpstan-return iterable<array{mixed, mixed}>
     */
    public function provideInputForValuesEqual(): iterable
    {
        yield from $this->provideInputForFromHash();
    }

    abstract protected function createFieldTypeUnderTest(): FieldType & Comparable;

    protected function getFieldTypeUnderTest(): FieldType & Comparable
    {
        if (!isset($this->fieldTypeUnderTest)) {
            $this->fieldTypeUnderTest = $this->createFieldTypeUnderTest();
        }

        return $this->fieldTypeUnderTest;
    }

    /**
     * @dataProvider provideInputForValuesEqual
     */
    public function testValuesEqual(
        mixed $inputValue1Hash,
        SPIValue $inputValue2
    ): void {
        $fieldType = $this->getFieldTypeUnderTest();

        $inputValue1 = $fieldType->fromHash($inputValue1Hash);

        self::assertTrue(
            $fieldType->valuesEqual($inputValue1, $inputValue2),
            'valuesEqual() method did not create expected result.'
        );
    }
}
