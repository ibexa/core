<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\FieldType;

use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\FieldType\Value;

interface DefaultDataFieldStorage extends FieldStorage
{
    /**
     * Populates <code>$field</code> value property with default data based on the external data.
     *
     * <code>$field->value</code> is a {@see FieldValue} object.
     * This value holds the data as a {@see Value} based object, according to
     * the field type (e.g. for <code>TextLine</code>, it will be a {@see \Ibexa\Core\FieldType\TextLine\Value} object).
     */
    public function getDefaultFieldData(
        VersionInfo $versionInfo,
        Field $field
    ): void;
}
