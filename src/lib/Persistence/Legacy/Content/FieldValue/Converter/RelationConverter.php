<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;

use DOMDocument;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;

class RelationConverter implements Converter
{
    /**
     * Converts data from $value to $storageFieldValue.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\FieldValue $value
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue $storageFieldValue
     */
    public function toStorageValue(FieldValue $value, StorageFieldValue $storageFieldValue)
    {
        $storageFieldValue->dataInt = !empty($value->data['destinationContentId'])
            ? $value->data['destinationContentId']
            : null;
        $storageFieldValue->sortKeyInt = (int)$value->sortKey;
    }

    /**
     * Converts data from $value to $fieldValue.
     *
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue $value
     * @param \Ibexa\Contracts\Core\Persistence\Content\FieldValue $fieldValue
     */
    public function toFieldValue(StorageFieldValue $value, FieldValue $fieldValue)
    {
        $fieldValue->data = [
            'destinationContentId' => $value->dataInt ?: null,
        ];
        $fieldValue->sortKey = (int)$value->sortKeyInt;
    }

    /**
     * Converts field definition data in $fieldDef into $storageFieldDef.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition $fieldDef
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition $storageDef
     */
    public function toStorageFieldDefinition(FieldDefinition $fieldDef, StorageFieldDefinition $storageDef)
    {
        $fieldSettings = $fieldDef->fieldTypeConstraints->fieldSettings;
        $doc = new DOMDocument('1.0', 'utf-8');
        $root = $doc->createElement('related-objects');
        $doc->appendChild($root);

        $constraints = $doc->createElement('constraints');
        if (!empty($fieldSettings['selectionContentTypes'])) {
            foreach ($fieldSettings['selectionContentTypes'] as $typeIdentifier) {
                $allowedClass = $doc->createElement('allowed-class');
                $allowedClass->setAttribute('contentclass-identifier', $typeIdentifier);
                $constraints->appendChild($allowedClass);
                unset($allowedClass);
            }
        }
        $root->appendChild($constraints);

        $selectionType = $doc->createElement('selection_type');
        $selectionMethod = isset($fieldSettings['selectionMethod']) ? (int)$fieldSettings['selectionMethod'] : 0;
        $selectionType->setAttribute('value', $selectionMethod);
        $root->appendChild($selectionType);

        $rootDefaultLocation = $doc->createElement('root_default_location');
        $rootDefaultLocation->setAttribute('value', (bool)($fieldSettings['rootDefaultLocation'] ?? false));
        $root->appendChild($rootDefaultLocation);

        $defaultLocation = $doc->createElement('contentobject-placement');
        if (!empty($fieldSettings['selectionRoot'])) {
            $defaultLocation->setAttribute('node-id', (int)$fieldSettings['selectionRoot']);
        }
        $root->appendChild($defaultLocation);

        $doc->appendChild($root);
        $storageDef->dataText5 = $doc->saveXML();

        // BC: For Backwards Compatibility for legacy and in case of downgrades or data sharing
        // Selection method, 0 = browse, 1 = dropdown
        $storageDef->dataInt1 = $selectionMethod;

        // Selection root, location ID, or 0 if empty
        $storageDef->dataInt2 = (int)$fieldSettings['selectionRoot'];
    }

    /**
     * Converts field definition data in $storageDef into $fieldDef.
     *
     * <code>
     *   <?xml version="1.0" encoding="utf-8"?>
     *   <related-objects>
     *     <constraints>
     *       <allowed-class contentclass-identifier="blog_post"/>
     *     </constraints>
     *     <selection_type value="1"/>
     *     <contentobject-placement node-id="67"/>
     *   </related-objects>
     *
     *   <?xml version="1.0" encoding="utf-8"?>
     *   <related-objects>
     *     <constraints/>
     *     <selection_type value="0"/>
     *     <contentobject-placement/>
     *   </related-objects>
     * </code>
     *
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition $storageDef
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition $fieldDef
     */
    public function toFieldDefinition(StorageFieldDefinition $storageDef, FieldDefinition $fieldDef)
    {
        // default settings
        // use dataInt1 and dataInt2 fields as default for backward compatibility
        $fieldDef->fieldTypeConstraints->fieldSettings = [
            'selectionMethod' => $storageDef->dataInt1,
            'selectionRoot' => $storageDef->dataInt2 === 0 ? '' : $storageDef->dataInt2,
            'selectionContentTypes' => [],
        ];

        if ($storageDef->dataText5 === null) {
            return;
        }

        // read settings from storage
        $fieldSettings = &$fieldDef->fieldTypeConstraints->fieldSettings;
        $dom = new DOMDocument('1.0', 'utf-8');
        if (empty($storageDef->dataText5) || $dom->loadXML($storageDef->dataText5) !== true) {
            return;
        }

        if (
            ($selectionType = $dom->getElementsByTagName('selection_type')->item(0)) &&
            $selectionType->hasAttribute('value')
        ) {
            $fieldSettings['selectionMethod'] = (int)$selectionType->getAttribute('value');
        }

        if (
            ($defaultLocation = $dom->getElementsByTagName('contentobject-placement')->item(0)) &&
            $defaultLocation->hasAttribute('node-id')
        ) {
            $fieldSettings['selectionRoot'] = (int)$defaultLocation->getAttribute('node-id');
        }

        if (
            ($rootDefaultLocation = $dom->getElementsByTagName('root_default_location')->item(0)) &&
            $rootDefaultLocation->hasAttribute('value')
        ) {
            $fieldSettings['rootDefaultLocation'] = (bool)$rootDefaultLocation->getAttribute('value');
        }

        if (!($constraints = $dom->getElementsByTagName('constraints'))) {
            return;
        }

        foreach ($constraints->item(0)->getElementsByTagName('allowed-class') as $allowedClass) {
            $fieldSettings['selectionContentTypes'][] = $allowedClass->getAttribute('contentclass-identifier');
        }
    }

    /**
     * Returns the name of the index column in the attribute table.
     *
     * Returns the name of the index column the datatype uses, which is either
     * "sort_key_int" or "sort_key_string". This column is then used for
     * filtering and sorting for this type.
     *
     * @return string
     */
    public function getIndexColumn(): string
    {
        return 'sort_key_int';
    }
}
