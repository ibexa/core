<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Specification;

interface SpecificationInterface
{
    /**
     * @param mixed $item
     */
    public function isSatisfiedBy($item): bool;

    public function and(SpecificationInterface $other): SpecificationInterface;

    public function or(SpecificationInterface $other): SpecificationInterface;

    public function not(): SpecificationInterface;
}
