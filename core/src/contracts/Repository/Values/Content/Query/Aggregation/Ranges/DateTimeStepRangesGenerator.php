<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;

/**
 * Generates ranges for date and datetime aggregations with a fixed step.
 *
 * This generator creates ranges based on a start date, end date, and a step interval.
 * It supports both open and closed ranges on the left and right sides.
 *
 * @phpstan-implements \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges\RangesGeneratorInterface<\DateTimeInterface>
 */
final class DateTimeStepRangesGenerator implements RangesGeneratorInterface
{
    private DateTimeInterface $start;

    private DateTimeInterface $end;

    private DateInterval $step;

    private bool $isLeftOpen = true;

    private bool $isRightOpen = true;

    public function __construct(DateTimeInterface $start, DateTimeInterface $end)
    {
        $this->start = $start;
        $this->end = $end;
        $this->step = new DateInterval('P1D');
    }

    public function getStart(): DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(DateTimeInterface $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): DateTimeInterface
    {
        return $this->end;
    }

    public function setEnd(DateTimeInterface $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getStep(): DateInterval
    {
        return $this->step;
    }

    public function setStep(DateInterval $step): self
    {
        $this->step = $step;

        return $this;
    }

    public function isLeftOpen(): bool
    {
        return $this->isLeftOpen;
    }

    public function setLeftOpen(bool $isLeftOpen): void
    {
        $this->isLeftOpen = $isLeftOpen;
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
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<\DateTimeInterface>[]
     */
    public function generate(): array
    {
        if ($this->start == $this->end && $this->isLeftOpen && $this->isRightOpen) {
            return [
                Range::ofDateTime(Range::INF, Range::INF),
            ];
        }

        $ranges = [];

        if ($this->isLeftOpen) {
            $ranges[] = Range::ofDateTime(Range::INF, $this->start);
        }

        /** @var \DateTimeImmutable $current */
        $current = $this->start;
        if ($current instanceof DateTime) {
            $current = DateTimeImmutable::createFromMutable($current);
        }

        while ($current < $this->end) {
            $next = $current->add($this->step);
            $ranges[] = Range::ofDateTime($current, $next);
            $current = $next;
        }

        if ($this->isRightOpen) {
            $ranges[] = Range::ofDateTime($this->end, Range::INF);
        }

        return $ranges;
    }
}
