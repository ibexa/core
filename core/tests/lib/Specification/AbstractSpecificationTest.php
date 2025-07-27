<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Specification;

class AbstractSpecificationTest extends BaseSpecificationTestCase
{
    public function testSpecification(): void
    {
        $isStringSpecification = $this->getIsStringSpecification();
        $isTestStringSpecification = $this->getIsTestStringSpecification();

        self::assertTrue($isStringSpecification->isSatisfiedBy('test_string'));
        self::assertFalse($isStringSpecification->isSatisfiedBy(1234));
    }

    public function testSpecificationAnd(): void
    {
        $isStringSpecification = $this->getIsStringSpecification();
        $isTestStringSpecification = $this->getIsTestStringSpecification();

        self::assertTrue($isStringSpecification->and($isTestStringSpecification)->isSatisfiedBy('test'));
        self::assertFalse($isStringSpecification->and($isTestStringSpecification)->isSatisfiedBy('test_string'));
        self::assertFalse($isStringSpecification->and($isTestStringSpecification)->isSatisfiedBy(1234));
    }

    public function testSpecificationOr(): void
    {
        $isStringSpecification = $this->getIsStringSpecification();
        $isTestStringSpecification = $this->getIsTestStringSpecification();

        self::assertTrue($isStringSpecification->or($isTestStringSpecification)->isSatisfiedBy('test'));
        self::assertTrue($isStringSpecification->or($isTestStringSpecification)->isSatisfiedBy('test_string'));
        self::assertFalse($isStringSpecification->or($isTestStringSpecification)->isSatisfiedBy(1234));
    }

    public function testSpecificationNot(): void
    {
        $isStringSpecification = $this->getIsStringSpecification();

        self::assertFalse($isStringSpecification->not()->isSatisfiedBy('test_string'));
        self::assertTrue($isStringSpecification->not()->isSatisfiedBy(1234));
    }
}
