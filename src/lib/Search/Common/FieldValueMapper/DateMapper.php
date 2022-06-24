<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Search\Common\FieldValueMapper;

use DateTime;
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

    public function map(Field $field)
    {
        $value = $field->getValue();
        if (is_numeric($value)) {
            $date = new DateTime("@{$value}");
        } else {
            try {
                $date = new DateTime($value);
            } catch (Exception $e) {
                throw new InvalidArgumentException('Invalid date provided: ' . $value);
            }
        }

        return $date->format('Y-m-d\\TH:i:s\\Z');
    }
}

class_alias(DateMapper::class, 'eZ\Publish\Core\Search\Common\FieldValueMapper\DateMapper');
