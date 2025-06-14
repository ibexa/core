<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult;

use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult;

final class StatsAggregationResult extends AggregationResult
{
    /** @var float|null */
    public ?float $sum;

    private ?int $count;

    private ?float $min;

    private ?float $max;

    private ?float $avg;

    public function __construct(string $name, ?int $count, ?float $min, ?float $max, ?float $avg, ?float $sum)
    {
        parent::__construct($name);

        $this->count = $count;
        $this->min = $min;
        $this->max = $max;
        $this->avg = $avg;
        $this->sum = $sum;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function getMin(): ?float
    {
        return $this->min;
    }

    public function getMax(): ?float
    {
        return $this->max;
    }

    public function getAvg(): ?float
    {
        return $this->avg;
    }

    public function getSum(): ?float
    {
        return $this->sum;
    }
}
