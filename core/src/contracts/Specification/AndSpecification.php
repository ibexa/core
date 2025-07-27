<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Specification;

final class AndSpecification extends AbstractSpecification
{
    private SpecificationInterface $one;

    private SpecificationInterface $two;

    public function __construct(SpecificationInterface $one, SpecificationInterface $two)
    {
        $this->one = $one;
        $this->two = $two;
    }

    public function isSatisfiedBy($item): bool
    {
        return $this->one->isSatisfiedBy($item) && $this->two->isSatisfiedBy($item);
    }
}
