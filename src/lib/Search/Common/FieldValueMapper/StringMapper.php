<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType;

/**
 * @internal
 */
class StringMapper extends BaseStringMapper
{
    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof FieldType\StringField;
    }

    public function map(Field $field): string
    {
        return $this->convert($field->getValue());
    }
}
