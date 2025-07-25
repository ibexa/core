<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\DateAndTime;

use DateTime;
use DateTimeInterface;
use Exception;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for the DateAndTime field type.
 */
class Value extends BaseValue
{
    /**
     * Date content.
     */
    public readonly ?DateTimeInterface $value;

    /**
     * Date format to be used by {@link __toString()}.
     */
    public string $stringFormat = 'U';

    /**
     * Construct a new Value object and initialize with $dateTime.
     *
     * @param \DateTime|null $dateTime Date/Time as a DateTime object
     */
    public function __construct(?DateTimeInterface $dateTime = null)
    {
        $this->value = $dateTime;

        parent::__construct();
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public static function fromString(string $dateString): Value
    {
        try {
            return new self(new DateTime($dateString));
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
            return new self(new DateTime("@$timestamp"));
        } catch (Exception $e) {
            throw new InvalidArgumentValue('$timestamp', $timestamp, __CLASS__, $e);
        }
    }

    public function __toString(): string
    {
        if (null === $this->value) {
            return '';
        }

        return $this->value->format($this->stringFormat);
    }
}
