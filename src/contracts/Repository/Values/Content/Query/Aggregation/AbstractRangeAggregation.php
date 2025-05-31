<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;

abstract class AbstractRangeAggregation implements Aggregation
{
    /**
     * The name of the aggregation.
     */
    protected string $name;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range[] */
    protected array $ranges;

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range[] $ranges
     */
    public function __construct(string $name, array $ranges = [])
    {
        $this->name = $name;
        $this->ranges = $ranges;
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range[]
     */
    public function getRanges(): array
    {
        return $this->ranges;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
