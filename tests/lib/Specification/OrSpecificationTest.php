<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Specification;

use Ibexa\Contracts\Core\Specification\OrSpecification;

class OrSpecificationTest extends BaseSpecificationTestCase
{
    public function testOrSpecification(): void
    {
        $andSpecification = new OrSpecification(
            $this->getIsStringSpecification(),
            $this->getIsTestStringSpecification()
        );

        self::assertTrue($andSpecification->isSatisfiedBy('test'));
        self::assertTrue($andSpecification->isSatisfiedBy('test_string'));
        self::assertFalse($andSpecification->isSatisfiedBy(1234));
    }
}
