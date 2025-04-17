<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\MultipleIntegerField;

/**
 * @internal
 */
final class MultipleIntegerMapper extends BaseIntegerMapper
{
    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof MultipleIntegerField;
    }

    /**
     * @return int[]
     */
    public function map(Field $field): array
    {
        $values = [];

        foreach ((array)$field->getValue() as $value) {
            $values[] = $this->convert($value);
        }

        return $values;
    }
}
