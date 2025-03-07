<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;

use DOMDocument;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\FieldType\FieldSettings;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;

class SelectionConverter implements Converter
{
    /**
     * Converts data from $value to $storageFieldValue.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\FieldValue $value
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue $storageFieldValue
     */
    public function toStorageValue(FieldValue $value, StorageFieldValue $storageFieldValue)
    {
        $storageFieldValue->sortKeyString = $storageFieldValue->dataText = $value->sortKey;
    }

    /**
     * Converts data from $value to $fieldValue.
     *
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue $value
     * @param \Ibexa\Contracts\Core\Persistence\Content\FieldValue $fieldValue
     */
    public function toFieldValue(StorageFieldValue $value, FieldValue $fieldValue)
    {
        if ($value->dataText !== '') {
            $fieldValue->data = array_map(
                'intval',
                explode('-', $value->dataText)
            );
        } else {
            $fieldValue->data = [];
        }
        $fieldValue->sortKey = $value->sortKeyString;
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

        if (isset($fieldSettings['isMultiple'])) {
            $storageDef->dataInt1 = (int)$fieldSettings['isMultiple'];
        }

        if (!empty($fieldSettings['options'])) {
            $xml = $this->buildOptionsXml($fieldSettings['options']);
            $storageDef->dataText5 = $xml->saveXML();
        }

        if (!isset($fieldSettings['multilingualOptions'])) {
            return;
        }

        foreach ($fieldSettings['multilingualOptions'] as $languageCode => $option) {
            $xml = $this->buildOptionsXml($option);

            $storageDef->multilingualData[$languageCode]->dataText = $xml->saveXML();

            if ($fieldDef->mainLanguageCode === $languageCode) {
                $storageDef->dataText5 = $xml->saveXML();
            }
        }
    }

    /**
     * Converts field definition data in $storageDef into $fieldDef.
     *
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition $storageDef
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition $fieldDef
     */
    public function toFieldDefinition(StorageFieldDefinition $storageDef, FieldDefinition $fieldDef)
    {
        $options = [];
        $multiLingualOptions = [$fieldDef->mainLanguageCode => []];

        if (isset($storageDef->dataText5)) {
            $optionsXml = simplexml_load_string($storageDef->dataText5);
            if ($optionsXml !== false) {
                foreach ($optionsXml->options->option as $option) {
                    $options[(int)$option['id']] = (string)$option['name'];
                }
            }
        }

        if (isset($fieldDef->mainLanguageCode) && !empty($options)) {
            $multiLingualOptions[$fieldDef->mainLanguageCode] = $options;
        }

        foreach ($storageDef->multilingualData as $languageCode => $mlData) {
            $xml = simplexml_load_string($mlData->dataText);
            if ($xml !== false) {
                foreach ($xml->options->option as $option) {
                    $multiLingualOptions[$languageCode][(int)$option['id']] = (string)$option['name'];
                }
            }
        }

        $fieldDef->fieldTypeConstraints->fieldSettings = new FieldSettings(
            [
                'isMultiple' => !empty($storageDef->dataInt1) ? (bool)$storageDef->dataInt1 : false,
                'options' => $options,
                'multilingualOptions' => $multiLingualOptions,
            ]
        );

        // @todo: Can Selection store a default value in the DB?
        $fieldDef->defaultValue = new FieldValue();
        $fieldDef->defaultValue->data = [];
        $fieldDef->defaultValue->sortKey = '';
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
        return 'sort_key_string';
    }

    /**
     * @param string[] $selectionOptions
     *
     * @return \DOMDocument
     */
    private function buildOptionsXml(array $selectionOptions): DOMDocument
    {
        $xml = new DOMDocument('1.0', 'utf-8');
        $xml->appendChild(
            $selection = $xml->createElement('ezselection')
        );
        $selection->appendChild(
            $options = $xml->createElement('options')
        );
        foreach ($selectionOptions as $id => $name) {
            $options->appendChild(
                $option = $xml->createElement('option')
            );
            $option->setAttribute('id', $id);
            $option->setAttribute('name', $name);
        }

        return $xml;
    }
}
