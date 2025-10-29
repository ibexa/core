<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;

/**
 * Generates ranges for float values with a specified step.
 *
 * @phpstan-implements \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges\RangesGeneratorInterface<float>
 */
final class FloatStepRangesGenerator implements RangesGeneratorInterface
{
    private float $start;

    private float $end;

    private float $step = 1;

    private bool $isLeftOpen = true;

    private bool $isRightOpen = true;

    public function __construct(
        float $start,
        float $end
    ) {
        $this->start = $start;
        $this->end = $end;
    }

    public function getStart(): float
    {
        return $this->start;
    }

    public function setStart(float $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): float
    {
        return $this->end;
    }

    public function setEnd(float $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getStep(): float
    {
        return $this->step;
    }

    public function setStep(float $step): self
    {
        $this->step = $step;

        return $this;
    }

    public function isLeftOpen(): bool
    {
        return $this->isLeftOpen;
    }

    public function setLeftOpen(bool $isLeftOpen): self
    {
        $this->isLeftOpen = $isLeftOpen;

        return $this;
    }

    public function isRightOpen(): bool
    {
        return $this->isRightOpen;
    }

    public function setRightOpen(bool $isRightOpen): self
    {
        $this->isRightOpen = $isRightOpen;

        return $this;
    }

    /**
     * @return Range<float>[]
     */
    public function generate(): array
    {
        if ($this->start === $this->end && $this->isLeftOpen && $this->isRightOpen) {
            return [
                Range::ofFloat(Range::INF, Range::INF),
            ];
        }

        $ranges = [];

        if ($this->isLeftOpen) {
            $ranges[] = Range::ofFloat(Range::INF, $this->start);
        }

        $values = range($this->start, $this->end, $this->step);
        for ($i = 1, $count = count($values); $i < $count; ++$i) {
            $ranges[] = Range::ofFloat($values[$i - 1], $values[$i]);
        }

        if ($this->isRightOpen) {
            $ranges[] = Range::ofFloat($this->end, Range::INF);
        }

        return $ranges;
    }
}
