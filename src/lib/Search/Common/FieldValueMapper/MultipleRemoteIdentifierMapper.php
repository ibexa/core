<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\MultipleRemoteIdentifierField;

/**
 * Common remote ID list field value mapper implementation.
 */
final class MultipleRemoteIdentifierMapper extends RemoteIdentifierMapper
{
    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof MultipleRemoteIdentifierField;
    }

    public function map(Field $field)
    {
        $values = [];

        foreach ($field->getValue() as $value) {
            $values[] = $this->convert($value);
        }

        return $values;
    }
}
