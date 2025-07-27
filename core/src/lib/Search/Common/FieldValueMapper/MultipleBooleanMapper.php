<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\MultipleBooleanField;
use Ibexa\Core\Search\Common\FieldValueMapper;

/**
 * Common multiple boolean field value mapper implementation.
 */
class MultipleBooleanMapper extends FieldValueMapper
{
    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof MultipleBooleanField;
    }

    public function map(Field $field)
    {
        $values = [];

        foreach ((array)$field->getValue() as $value) {
            $values[] = (bool)$value;
        }

        return $values;
    }
}
