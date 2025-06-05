<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;

use DOMDocument;
use Ibexa\Contracts\Core\Exception\InvalidArgumentException;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\FieldType\Author\Type as AuthorType;
use Ibexa\Core\FieldType\FieldSettings;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;

class AuthorConverter implements Converter
{
    /**
     * @throws \DOMException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function toStorageValue(FieldValue $value, StorageFieldValue $storageFieldValue)
    {
        $storageFieldValue->dataText = $this->generateXmlString($value->data);
        $storageFieldValue->sortKeyString = $value->sortKey;
    }

    /**
     * Converts data from $value to $fieldValue.
     *
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue $value
     * @param \Ibexa\Contracts\Core\Persistence\Content\FieldValue $fieldValue
     */
    public function toFieldValue(StorageFieldValue $value, FieldValue $fieldValue)
    {
        $fieldValue->data = $this->restoreValueFromXmlString($value->dataText);
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

        if ($fieldSettings !== null) {
            $storageDef->dataInt1 = (int)$fieldSettings['defaultAuthor'];
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
        $fieldDef->fieldTypeConstraints->fieldSettings = new FieldSettings(
            [
                'defaultAuthor' => $storageDef->dataInt1 ?? AuthorType::DEFAULT_VALUE_EMPTY,
            ]
        );

        $fieldDef->defaultValue->data = [];
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
     * Generates XML string from $authorValue to be stored in storage engine.
     *
     * @param array $authorValue
     *
     * @return string The generated XML string
     *
     * @throws \DOMException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function generateXmlString(array $authorValue): string
    {
        $doc = new DOMDocument('1.0', 'utf-8');

        $root = $doc->createElement('ibexa_author');
        $doc->appendChild($root);

        $authors = $doc->createElement('authors');
        $root->appendChild($authors);

        foreach ($authorValue as $author) {
            $authorNode = $doc->createElement('author');
            $authorNode->setAttribute('id', (string)$author['id']);
            $authorNode->setAttribute('name', $author['name']);
            $authorNode->setAttribute('email', $author['email']);
            $authors->appendChild($authorNode);
            unset($authorNode);
        }

        $xml = $doc->saveXML();
        if (false === $xml) {
            $lastError = libxml_get_last_error();
            throw new InvalidArgumentException(
                '$authorValue',
                sprintf(
                    'AuthorConverter: an error occurred when trying to save author field data: %s',
                    $lastError !== false ? $lastError->message : 'unknown error'
                )
            );
        }

        return $xml;
    }

    /**
     * Restores an author Value object from $xmlString.
     *
     * @param string $xmlString XML String stored in storage engine
     *
     * @return \Ibexa\Core\FieldType\Author\Value[]
     */
    private function restoreValueFromXmlString($xmlString)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $authors = [];

        if ($dom->loadXML($xmlString) === true) {
            foreach ($dom->getElementsByTagName('author') as $author) {
                $authors[] = [
                    'id' => $author->getAttribute('id'),
                    'name' => $author->getAttribute('name'),
                    'email' => $author->getAttribute('email'),
                ];
            }
        }

        return $authors;
    }
}
