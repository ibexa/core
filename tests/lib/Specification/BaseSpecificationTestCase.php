<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Specification;

use Ibexa\Contracts\Core\Specification\AbstractSpecification;
use Ibexa\Contracts\Core\Specification\SpecificationInterface;
use PHPUnit\Framework\TestCase;

abstract class BaseSpecificationTestCase extends TestCase
{
    protected function getIsStringSpecification(): SpecificationInterface
    {
        return new class() extends AbstractSpecification {
            public function isSatisfiedBy($item): bool
            {
                return is_string($item);
            }
        };
    }

    protected function getIsTestStringSpecification(): SpecificationInterface
    {
        return new class() extends AbstractSpecification {
            public function isSatisfiedBy($item): bool
            {
                return $item === 'test';
            }
        };
    }
}
