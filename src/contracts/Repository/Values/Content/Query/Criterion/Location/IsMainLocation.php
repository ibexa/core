<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Location;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use InvalidArgumentException;

/**
 * A criterion that matches Location based on if it is main Location or not.
 */
class IsMainLocation extends Location implements FilteringCriterion
{
    /**
     * Main constant: is main.
     */
    public const int MAIN = 0;

    /**
     * Main constant: is not main.
     */
    public const int NOT_MAIN = 1;

    /**
     * Creates a new IsMainLocation criterion.
     *
     * @throws \InvalidArgumentException
     *
     * @param int $value one of self::MAIN and self::NOT_MAIN
     */
    public function __construct(int $value)
    {
        if ($value !== self::MAIN && $value !== self::NOT_MAIN) {
            throw new InvalidArgumentException("Invalid main status value $value");
        }

        parent::__construct(null, null, $value);
    }

    public function getSpecifications(): array
    {
        return [
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                Specifications::TYPE_INTEGER
            ),
        ];
    }
}
