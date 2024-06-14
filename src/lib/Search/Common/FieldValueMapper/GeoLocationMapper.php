<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\GeoLocationField;
use Ibexa\Core\Search\Common\FieldValueMapper;

/**
 * Common geo location field value mapper implementation.
 */
class GeoLocationMapper extends FieldValueMapper
{
    public function canMap(Field $field): bool
    {
        return $field->getType() instanceof GeoLocationField;
    }

    public function map(Field $field): ?string
    {
        $value = $field->getValue();
        if ($value['latitude'] === null || $value['longitude'] === null) {
            return null;
        }

        return sprintf('%F,%F', $value['latitude'], $value['longitude']);
    }
}
