<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\DocumentField;
use Ibexa\Core\Search\Common\FieldValueMapper;

/**
 * Common document field value mapper implementation.
 */
class DocumentMapper extends FieldValueMapper
{
    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof DocumentField;
    }

    public function map(Field $field)
    {
        return $field->getValue();
    }
}
