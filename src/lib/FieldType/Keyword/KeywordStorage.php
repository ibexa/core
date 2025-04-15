<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Keyword;

use Ibexa\Contracts\Core\FieldType\GatewayBasedStorage;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

/**
 * Converter for Keyword field type external storage.
 *
 * The keyword storage ships a list (array) of keywords in
 * $field->value->externalData. $field->value->data is simply empty, because no
 * internal data is store.
 *
 * @extends \Ibexa\Contracts\Core\FieldType\GatewayBasedStorage<\Ibexa\Core\FieldType\Keyword\KeywordStorage\Gateway>
 */
class KeywordStorage extends GatewayBasedStorage
{
    /**
     * @see \Ibexa\Contracts\Core\FieldType\FieldStorage
     */
    public function storeFieldData(VersionInfo $versionInfo, Field $field): bool
    {
        $contentTypeId = $this->gateway->getContentTypeId($field);

        $this->gateway->storeFieldData($field, $contentTypeId);

        return true;
    }

    public function getFieldData(VersionInfo $versionInfo, Field $field): void
    {
        $this->gateway->getFieldData($field);
    }

    /**
     * @param int[] $fieldIds
     */
    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds): bool
    {
        foreach ($fieldIds as $fieldId) {
            $this->gateway->deleteFieldData($fieldId, $versionInfo->versionNo);
        }

        return true;
    }

    public function hasFieldData(): bool
    {
        return true;
    }
}
