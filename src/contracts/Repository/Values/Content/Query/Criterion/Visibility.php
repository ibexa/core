<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use InvalidArgumentException;

/**
 * A criterion that matches content based on its visibility.
 *
 * Warning: This Criterion acts on all locations of a Content, so it will include hidden
 * content within the tree you are searching for if content has visible location elsewhere.
 * This is intentional and you should rather use LocationSearch if this is not the behaviour you want.
 */
class Visibility extends Criterion implements FilteringCriterion
{
    /**
     * Visibility constant: visible.
     */
    public const int VISIBLE = 0;

    /**
     * Visibility constant: hidden.
     */
    public const int HIDDEN = 1;

    /**
     * Creates a new Visibility criterion.
     *
     * @param int $value Visibility: self::VISIBLE, self::HIDDEN
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(int $value)
    {
        if ($value !== self::VISIBLE && $value !== self::HIDDEN) {
            throw new InvalidArgumentException("Invalid visibility value $value");
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
