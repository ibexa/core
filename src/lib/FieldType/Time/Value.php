<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Time;

use DateTime;
use DateTimeInterface;
use Exception;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for the Time field type.
 */
class Value extends BaseValue
{
    /**
     * Time of day as number of seconds.
     */
    public readonly ?int $time;

    /**
     * Time format to be used by {@link __toString()}.
     */
    public string $stringFormat = 'H:i:s';

    /**
     * Construct a new Value object and initialize it with $seconds as number of seconds from the beginning of a day.
     */
    public function __construct(?int $seconds = null)
    {
        $this->time = $seconds;

        parent::__construct();
    }

    /**
     * Creates a Value from the given $dateTime.
     */
    public static function fromDateTime(DateTimeInterface $dateTime): Value
    {
        $dateTime = clone $dateTime;

        return new self($dateTime->getTimestamp() - $dateTime->setTime(0, 0, 0)->getTimestamp());
    }

    /**
     * Creates a Value from the given $timeString.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public static function fromString(string $timeString): Value
    {
        try {
            return static::fromDateTime(new DateTime($timeString));
        } catch (Exception $e) {
            throw new InvalidArgumentValue('$timeString', $timeString, __CLASS__, $e);
        }
    }

    /**
     * Creates a Value from the given $timestamp.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public static function fromTimestamp(int $timestamp): Value
    {
        try {
            $dateTime = new DateTime("@{$timestamp}");

            return static::fromDateTime($dateTime);
        } catch (Exception $e) {
            throw new InvalidArgumentValue('$timestamp', $timestamp, __CLASS__, $e);
        }
    }

    public function __toString(): string
    {
        if ($this->time === null) {
            return '';
        }

        return (new DateTime("@{$this->time}"))->format($this->stringFormat);
    }
}
