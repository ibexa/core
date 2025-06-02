<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Specification;

use Ibexa\Contracts\Core\Specification\AndSpecification;

class AndSpecificationTest extends BaseSpecificationTestCase
{
    public function testAndSpecification(): void
    {
        $andSpecification = new AndSpecification(
            $this->getIsStringSpecification(),
            $this->getIsTestStringSpecification()
        );

        self::assertTrue($andSpecification->isSatisfiedBy('test'));
        self::assertFalse($andSpecification->isSatisfiedBy('test_string'));
        self::assertFalse($andSpecification->isSatisfiedBy(1234));
    }
}
