<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Date;

use DateTime;
use DateTimeZone;
use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for Date field type.
 * Date should always be represented in UTC.
 */
class Value extends BaseValue
{
    /**
     * Date content.
     *
     * @var DateTime|null
     */
    public $date;

    /**
     * Date format to be used by {@link __toString()}.
     *
     * @var string
     */
    public $stringFormat = 'l d F Y';

    /**
     * Construct a new Value object and initialize with $dateTime.
     *
     * @param DateTime|null $dateTime Date as a DateTime object
     */
    public function __construct(?DateTime $dateTime = null)
    {
        if ($dateTime !== null) {
            $dateTime = clone $dateTime;
            $dateTime->setTime(0, 0, 0);
        }
        $this->date = $dateTime;
    }

    /**
     * Creates a Value from the given $dateString.
     *
     * @throws InvalidArgumentException
     *
     * @param string $dateString
     *
     * @return Value
     */
    public static function fromString($dateString)
    {
        try {
            return new static(new DateTime($dateString, new DateTimeZone('UTC')));
        } catch (Exception $e) {
            throw new InvalidArgumentValue('$dateString', $dateString, __CLASS__, $e);
        }
    }

    /**
     * Creates a Value from the given $timestamp.
     *
     * @throws InvalidArgumentException
     *
     * @param int $timestamp
     *
     * @return Value
     */
    public static function fromTimestamp($timestamp)
    {
        try {
            return new static(new DateTime("@{$timestamp}"));
        } catch (Exception $e) {
            throw new InvalidArgumentValue('$timestamp', $timestamp, __CLASS__, $e);
        }
    }

    public function __toString()
    {
        if (!$this->date instanceof DateTime) {
            return '';
        }

        return $this->date->format($this->stringFormat);
    }
}
