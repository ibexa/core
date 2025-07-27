<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * @phpstan-template TValue
 */
final class Range extends ValueObject
{
    public const INF = null;

    /**
     * Beginning of the range (included).
     *
     * @phpstan-var TValue|null
     */
    private mixed $from;

    /**
     * End of the range (excluded).
     *
     * @phpstan-var TValue|null
     */
    private mixed $to;

    private ?string $label;

    /**
     * @phpstan-param TValue|null $from beginning of the range (included).
     * @phpstan-param TValue|null $to end of the range (excluded).
     */
    public function __construct(mixed $from, mixed $to, ?string $label = null)
    {
        parent::__construct();

        $this->from = $from;
        $this->to = $to;
        $this->label = $label;
    }

    /**
     * @phpstan-return TValue|null
     */
    public function getFrom(): mixed
    {
        return $this->from;
    }

    /**
     * @phpstan-return TValue|null
     */
    public function getTo(): mixed
    {
        return $this->to;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function __toString(): string
    {
        if ($this->label !== null) {
            return sprintf(
                '%s:[%s;%s)',
                $this->label,
                $this->getRangeValueAsString($this->from),
                $this->getRangeValueAsString($this->to)
            );
        }

        return sprintf(
            '[%s;%s)',
            $this->getRangeValueAsString($this->from),
            $this->getRangeValueAsString($this->to)
        );
    }

    /**
     * Check if the range is equal to another range.
     *
     * @phpstan-param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<TValue> $value
     */
    public function equalsTo(Range $value): bool
    {
        return $this->from == $value->from && $this->to == $value->to;
    }

    /**
     * Returns the string representation of the range value.
     *
     * If the value is null, it returns '*'.
     * If the value is a DateTimeInterface, it formats it to ISO8601.
     * Otherwise, it casts the value to a string.
     *
     * @phpstan-param TValue|null $value
     */
    private function getRangeValueAsString(mixed $value): string
    {
        if ($value === null) {
            return '*';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ISO8601);
        }

        return (string)$value;
    }

    /**
     * Creates a range of integers.
     *
     * @phpstan-return \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<int>
     */
    public static function ofInt(?int $from, ?int $to): self
    {
        return new self($from, $to);
    }

    /**
     * Creates a range of floats.
     *
     * @phpstan-return \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<float>
     */
    public static function ofFloat(?float $from, ?float $to): self
    {
        return new self($from, $to);
    }

    /**
     * Creates a range of dates.
     *
     * @phpstan-return \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<\DateTimeInterface>
     */
    public static function ofDateTime(?DateTimeInterface $from, ?DateTimeInterface $to): self
    {
        return new self($from, $to);
    }
}
