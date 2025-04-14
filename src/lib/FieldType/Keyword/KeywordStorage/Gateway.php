<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Keyword\KeywordStorage;

use Ibexa\Contracts\Core\FieldType\StorageGateway;
use Ibexa\Contracts\Core\Persistence\Content\Field;

/**
 * Keyword Field Type external storage gateway.
 */
abstract class Gateway extends StorageGateway
{
    /**
     * @see \Ibexa\Contracts\Core\FieldType\FieldStorage::storeFieldData()
     */
    abstract public function storeFieldData(Field $field, int $contentTypeId): void;

    /**
     * Sets the list of assigned keywords into $field->value->externalData.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     */
    abstract public function getFieldData(Field $field): void;

    /**
     * Retrieve the ContentType ID for the given $field.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     */
    abstract public function getContentTypeId(Field $field): int;

    /**
     * @see \Ibexa\Contracts\Core\FieldType\FieldStorage::deleteFieldData()
     */
    abstract public function deleteFieldData(int $fieldId, int $versionNo): void;
}
