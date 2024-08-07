<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\IntegerField;
use Ibexa\Core\Search\Common\FieldValueMapper;

/**
 * Common integer field value mapper implementation.
 */
class IntegerMapper extends FieldValueMapper
{
    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof IntegerField;
    }

    public function map(Field $field)
    {
        return $this->convert($field->getValue());
    }

    /**
     * Convert to a proper search engine representation.
     *
     * @param mixed $value
     */
    protected function convert($value): int
    {
        return (int)$value;
    }
}

class_alias(IntegerMapper::class, 'eZ\Publish\Core\Search\Common\FieldValueMapper\IntegerMapper');
