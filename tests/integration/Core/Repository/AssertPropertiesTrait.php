<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

trait AssertPropertiesTrait
{
    /**
     * Asserts that properties given in $expectedValues are correctly set in
     * $actualObject.
     *
     * @param mixed[] $expectedValues
     * @param \Ibexa\Contracts\Core\Repository\Values\ValueObject $actualObject
     */
    protected function assertPropertiesCorrect(array $expectedValues, ValueObject $actualObject): void
    {
        foreach ($expectedValues as $propertyName => $propertyValue) {
            if ($propertyValue instanceof ValueObject) {
                $this->assertStructPropertiesCorrect($propertyValue, $actualObject->$propertyName);
            } elseif (is_array($propertyValue)) {
                foreach ($propertyValue as $key => $value) {
                    if ($value instanceof ValueObject) {
                        $this->assertStructPropertiesCorrect($value, $actualObject->$propertyName[$key]);
                    } else {
                        $this->assertPropertiesEqual("$propertyName\[$key\]", $value, $actualObject->$propertyName[$key]);
                    }
                }
            } else {
                $this->assertPropertiesEqual($propertyName, $propertyValue, $actualObject->$propertyName);
            }
        }
    }

    /**
     * Asserts that properties given in $expectedValues are correctly set in
     * $actualObject.
     *
     * If the property type is array, it will be sorted before comparison.
     *
     * @TODO: introduced because of randomly failing tests, ref: https://issues.ibexa.co/browse/EZP-21734
     *
     * @param mixed[] $expectedValues
     * @param \Ibexa\Contracts\Core\Repository\Values\ValueObject $actualObject
     */
    protected function assertPropertiesCorrectUnsorted(array $expectedValues, ValueObject $actualObject): void
    {
        foreach ($expectedValues as $propertyName => $propertyValue) {
            if ($propertyValue instanceof ValueObject) {
                $this->assertStructPropertiesCorrect($propertyValue, $actualObject->$propertyName);
            } else {
                $this->assertPropertiesEqual($propertyName, $propertyValue, $actualObject->$propertyName, true);
            }
        }
    }

    /**
     * Asserts all properties from $expectedValues are correctly set in
     * $actualObject. Additional (virtual) properties can be asserted using
     * $additionalProperties.
     *
     * @param array<string> $additionalProperties
     */
    protected function assertStructPropertiesCorrect(
        ValueObject $expectedValues,
        ValueObject $actualObject,
        array $additionalProperties = [],
    ): void {
        foreach ($expectedValues as $propertyName => $propertyValue) {
            if ($propertyValue instanceof ValueObject) {
                $this->assertStructPropertiesCorrect($propertyValue, $actualObject->$propertyName);
            } else {
                $this->assertPropertiesEqual($propertyName, $propertyValue, $actualObject->$propertyName);
            }
        }

        foreach ($additionalProperties as $propertyName) {
            $this->assertPropertiesEqual($propertyName, $expectedValues->$propertyName, $actualObject->$propertyName);
        }
    }

    /**
     * @param array<scalar> $items An array of scalar values
     *
     * @see \Ibexa\Tests\Integration\Core\Repository\BaseTestCase::assertPropertiesCorrectUnsorted
     */
    private function sortItems(array &$items): void
    {
        $sorter = static function ($a, $b): int {
            if (!is_scalar($a) || !is_scalar($b)) {
                self::fail('Wrong usage: method ' . __METHOD__ . ' accepts only an array of scalar values');
            }

            return strcmp($a, $b);
        };
        usort($items, $sorter);
    }

    private function assertPropertiesEqual($propertyName, $expectedValue, $actualValue, $sortArray = false): void
    {
        if ($expectedValue instanceof \ArrayObject) {
            $expectedValue = $expectedValue->getArrayCopy();
        } elseif ($expectedValue instanceof \DateTimeInterface) {
            $expectedValue = $expectedValue->format(\DateTime::RFC850);
        }
        if ($actualValue instanceof \ArrayObject) {
            $actualValue = $actualValue->getArrayCopy();
        } elseif ($actualValue instanceof \DateTimeInterface) {
            $actualValue = $actualValue->format(\DateTime::RFC850);
        }

        if ($sortArray && is_array($actualValue) && is_array($expectedValue)) {
            $this->sortItems($actualValue);
            $this->sortItems($expectedValue);
        }

        self::assertEquals(
            $expectedValue,
            $actualValue,
            sprintf('Object property "%s" incorrect.', $propertyName)
        );
    }
}
