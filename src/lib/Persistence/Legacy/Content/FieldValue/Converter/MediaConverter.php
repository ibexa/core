<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;

use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\FieldType\FieldSettings;
use Ibexa\Core\FieldType\Media\Type as MediaType;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;

class MediaConverter extends BinaryFileConverter
{
    /**
     * Converts field definition data in $fieldDef into $storageFieldDef.
     *
     * @param FieldDefinition $fieldDef
     * @param StorageFieldDefinition $storageDef
     */
    public function toStorageFieldDefinition(
        FieldDefinition $fieldDef,
        StorageFieldDefinition $storageDef
    ) {
        parent::toStorageFieldDefinition($fieldDef, $storageDef);

        $storageDef->dataText1 = (isset($fieldDef->fieldTypeConstraints->fieldSettings['mediaType'])
            ? $fieldDef->fieldTypeConstraints->fieldSettings['mediaType']
            : MediaType::TYPE_HTML5_VIDEO);
    }

    /**
     * Converts field definition data in $storageDef into $fieldDef.
     *
     * @param StorageFieldDefinition $storageDef
     * @param FieldDefinition $fieldDef
     */
    public function toFieldDefinition(
        StorageFieldDefinition $storageDef,
        FieldDefinition $fieldDef
    ) {
        parent::toFieldDefinition($storageDef, $fieldDef);
        $fieldDef->fieldTypeConstraints->fieldSettings = new FieldSettings(
            [
                'mediaType' => $storageDef->dataText1,
            ]
        );
    }

    public function getIndexColumn(): string
    {
        return '';
    }
}
