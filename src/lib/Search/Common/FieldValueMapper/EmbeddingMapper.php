<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\EmbeddingField;
use Ibexa\Core\Search\Common\FieldValueMapper;

/**
 * @internal for internal use by Search engine field value mapper
 */
final class EmbeddingMapper extends FieldValueMapper
{
    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof EmbeddingField;
    }

    public function map(Field $field)
    {
        return $field->getValue();
    }
}
