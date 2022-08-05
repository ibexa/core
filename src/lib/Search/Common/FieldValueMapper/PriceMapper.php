<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\PriceField;
use Ibexa\Core\Search\Common\FieldValueMapper;

/**
 * Common price field value mapper implementation.
 */
class PriceMapper extends FieldValueMapper
{
    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof PriceField;
    }

    /**
     * Map field value to a proper search engine representation.
     *
     * @param \Ibexa\Contracts\Core\Search\Field $field
     *
     * @return mixed
     */
    public function map(Field $field)
    {
        return (float)$field->getValue();
    }
}

class_alias(PriceMapper::class, 'eZ\Publish\Core\Search\Common\FieldValueMapper\PriceMapper');
