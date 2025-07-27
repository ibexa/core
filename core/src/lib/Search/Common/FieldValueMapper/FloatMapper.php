<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\FloatField;
use Ibexa\Core\Search\Common\FieldValueMapper;

/**
 * Common float field value mapper implementation.
 */
class FloatMapper extends FieldValueMapper
{
    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof FloatField;
    }

    public function map(Field $field): string
    {
        return sprintf('%F', (float)$field->getValue());
    }
}
