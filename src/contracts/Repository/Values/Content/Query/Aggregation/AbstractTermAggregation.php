<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;

abstract class AbstractTermAggregation implements Aggregation
{
    public const int DEFAULT_LIMIT = 10;
    public const int DEFAULT_MIN_COUNT = 1;

    /**
     * The name of the aggregation.
     */
    protected string $name;

    /**
     * Number of facets (terms) returned.
     */
    protected int $limit = self::DEFAULT_LIMIT;

    /**
     * Specifies the minimum count. Only facet groups with more or equal results are returned.
     */
    protected int $minCount = self::DEFAULT_MIN_COUNT;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getMinCount(): int
    {
        return $this->minCount;
    }

    public function setMinCount(int $minCount): self
    {
        $this->minCount = $minCount;

        return $this;
    }
}
