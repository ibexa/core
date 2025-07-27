<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use DateTime;
use DateTimeInterface;
use Exception;
use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\DateField;
use Ibexa\Core\Search\Common\FieldValueMapper;
use InvalidArgumentException;

/**
 * Common date field value mapper implementation.
 */
class DateMapper extends FieldValueMapper
{
    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof DateField;
    }

    public function map(Field $field): string
    {
        $date = $field->getValue();

        if (!$date instanceof DateTimeInterface) {
            $date = $this->convertToDateTime($date);
        }

        return $date->format('Y-m-d\\TH:i:s\\Z');
    }

    /**
     * @param string|int $value
     */
    private function convertToDateTime($value): DateTimeInterface
    {
        if (is_numeric($value)) {
            return new DateTime("@{$value}");
        }

        try {
            return new DateTime($value);
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid date provided: ' . $value);
        }
    }
}
