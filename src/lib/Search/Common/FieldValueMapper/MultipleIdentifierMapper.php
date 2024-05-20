<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\MultipleIdentifierField;

/**
 * Common multiple identifier field value mapper implementation.
 */
class MultipleIdentifierMapper extends IdentifierMapper
{
    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof MultipleIdentifierField;
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
        $values = [];

        $isRaw = $field->getType()->raw;
        foreach ($field->getValue() as $value) {
            if (!$isRaw) {
                $value = $this->convert($value);
            }

            $values[] = $value;
        }

        return $values;
    }
}
