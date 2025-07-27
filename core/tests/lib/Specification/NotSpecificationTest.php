<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Specification;

use Ibexa\Contracts\Core\Specification\NotSpecification;

class NotSpecificationTest extends BaseSpecificationTestCase
{
    public function testNotSpecification(): void
    {
        $andSpecification = new NotSpecification(
            $this->getIsStringSpecification()
        );

        self::assertFalse($andSpecification->isSatisfiedBy('test'));
        self::assertTrue($andSpecification->isSatisfiedBy(1234));
    }
}
