<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\IntegerField;

/**
 * @internal
 */
final class IntegerMapper extends BaseIntegerMapper
{
    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof IntegerField;
    }

    public function map(Field $field): int
    {
        return $this->convert($field->getValue());
    }
}
