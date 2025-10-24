<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Values;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

trait ValueObjectTestTrait
{
    /**
     * Asserts that properties given in $expectedValues are correctly set in
     * $mockedValueObject.
     *
     * @param mixed[] $expectedValues
     * @param ValueObject $actualValueObject
     */
    public function assertPropertiesCorrect(
        array $expectedValues,
        ValueObject $actualValueObject
    ) {
        foreach ($expectedValues as $propertyName => $propertyValue) {
            self::assertSame(
                $propertyValue,
                $actualValueObject->$propertyName,
                sprintf('Property %s value is incorrect', $propertyName)
            );
        }
    }
}
