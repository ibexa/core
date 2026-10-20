<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType;

/**
 * Common multiple string field value mapper implementation.
 */
class MultipleStringMapper extends StringMapper
{
    public function canMap(Field $field): bool
    {
        $searchFieldType = $field->getType();

        return
            $searchFieldType instanceof FieldType\MultipleStringField ||
            $searchFieldType instanceof FieldType\TextField ||
            $searchFieldType instanceof FieldType\FullTextField;
    }

    /**
     * Map field value to a proper search engine representation.
     *
     * @param Field $field
     *
     * @return array
     */
    public function map(Field $field)
    {
        $values = [];

        foreach ((array)$field->getValue() as $value) {
            $values[] = $this->convert($value);
        }

        return $values;
    }
}
