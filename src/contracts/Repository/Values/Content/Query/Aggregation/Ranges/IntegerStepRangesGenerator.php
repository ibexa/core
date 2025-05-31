<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges;

use Generator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;

/**
 * Generates ranges for float values with a specified step.
 *
 * @phpstan-implements \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges\RangesGeneratorInterface<int>
 */
final class IntegerStepRangesGenerator implements RangesGeneratorInterface
{
    private int $start;

    private int $end;

    private int $step = 1;

    private bool $isLeftOpen = true;

    private bool $isRightOpen = true;

    public function __construct(int $start, int $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function setStart(int $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    public function setEnd(int $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getStep(): int
    {
        return $this->step;
    }

    public function setStep(int $step): self
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
     * @phpstan-return \Generator<\Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<int>>
     */
    public function generate(): Generator
    {
        if ($this->start === $this->end && $this->isLeftOpen && $this->isRightOpen) {
            yield Range::ofInt(Range::INF, Range::INF);

            return;
        }

        if ($this->isLeftOpen) {
            yield Range::ofInt(Range::INF, $this->start);
        }

        $values = range($this->start, $this->end, $this->step);
        for ($i = 1, $count = count($values); $i < $count; ++$i) {
            yield Range::ofInt($values[$i - 1], $values[$i]);
        }

        if ($this->isRightOpen) {
            yield Range::ofInt($this->end, Range::INF);
        }
    }
}
