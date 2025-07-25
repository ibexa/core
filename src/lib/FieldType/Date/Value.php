<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Date;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for the Date field type.
 * Date should always be represented in UTC.
 */
class Value extends BaseValue
{
    /**
     * Date content.
     */
    public readonly ?DateTimeInterface $date;

    /**
     * Date format to be used by {@link __toString()}.
     */
    public string $stringFormat = 'l d F Y';

    /**
     * @param \DateTime|null $dateTime Date as a DateTime object
     */
    public function __construct(?DateTimeInterface $dateTime = null)
    {
        if ($dateTime !== null) {
            $dateTime = clone $dateTime;
            $dateTime->setTime(0, 0, 0);
        }
        $this->date = $dateTime;

        parent::__construct();
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public static function fromString(string $dateString): Value
    {
        try {
            return new self(new DateTime($dateString, new DateTimeZone('UTC')));
        } catch (Exception $e) {
            throw new InvalidArgumentValue('$dateString', $dateString, __CLASS__, $e);
        }
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public static function fromTimestamp(int $timestamp): Value
    {
        try {
            return new self(new DateTime("@{$timestamp}"));
        } catch (Exception $e) {
            throw new InvalidArgumentValue('$timestamp', $timestamp, __CLASS__, $e);
        }
    }

    public function __toString(): string
    {
        if (!$this->date instanceof DateTime) {
            return '';
        }

        return $this->date->format($this->stringFormat);
    }
}
