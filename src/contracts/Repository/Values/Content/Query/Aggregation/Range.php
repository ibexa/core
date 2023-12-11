<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

final class Range extends ValueObject
{
    public const INF = null;

    /**
     * Beginning of the range (included).
     *
     * @var int|float|\DateTimeInterface|null
     */
    private $from;

    /**
     * End of the range (excluded).
     *
     * @var int|float|\DateTimeInterface|null
     */
    private $to;

    private ?string $label;

    public function __construct($from, $to, ?string $label = null)
    {
        parent::__construct();

        $this->from = $from;
        $this->to = $to;
        $this->label = $label;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getTo()
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

    public function equalsTo(Range $value): bool
    {
        return $this->from == $value->from && $this->to == $value->to;
    }

    private function getRangeValueAsString($value): string
    {
        if ($value === null) {
            return '*';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ISO8601);
        }

        return (string)$value;
    }

    public static function ofInt(?int $from, ?int $to): self
    {
        return new self($from, $to);
    }

    public static function ofFloat(?float $from, ?float $to): self
    {
        return new self($from, $to);
    }

    public static function ofDateTime(?DateTimeInterface $from, ?DateTimeInterface $to): self
    {
        return new self($from, $to);
    }
}

class_alias(Range::class, 'eZ\Publish\API\Repository\Values\Content\Query\Aggregation\Range');
