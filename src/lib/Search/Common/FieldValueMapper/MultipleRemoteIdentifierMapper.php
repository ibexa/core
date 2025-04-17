<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\MultipleRemoteIdentifierField;

/**
 * @internal
 */
final class MultipleRemoteIdentifierMapper extends BaseStringMapper
{
    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof MultipleRemoteIdentifierField;
    }

    /**
     * @return string[]
     */
    public function map(Field $field): array
    {
        $values = [];

        foreach ($field->getValue() as $value) {
            $values[] = $this->convert($value);
        }

        return $values;
    }
}
