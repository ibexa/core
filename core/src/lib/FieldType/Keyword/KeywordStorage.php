<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Keyword;

use Ibexa\Contracts\Core\FieldType\GatewayBasedStorage;
use Ibexa\Contracts\Core\FieldType\StorageGatewayInterface;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

/**
 * Converter for Keyword field type external storage.
 *
 * The keyword storage ships a list (array) of keywords in
 * $field->value->externalData. $field->value->data is simply empty, because no
 * internal data is store.
 */
class KeywordStorage extends GatewayBasedStorage
{
    /** @var \Ibexa\Core\FieldType\Keyword\KeywordStorage\Gateway */
    protected StorageGatewayInterface $gateway;

    /**
     * @see \Ibexa\Contracts\Core\FieldType\FieldStorage
     *
     * @return mixed
     */
    public function storeFieldData(VersionInfo $versionInfo, Field $field)
    {
        $contentTypeId = $this->gateway->getContentTypeId($field);

        return $this->gateway->storeFieldData($field, $contentTypeId);
    }

    public function getFieldData(VersionInfo $versionInfo, Field $field)
    {
        return $this->gateway->getFieldData($field);
    }

    /**
     * @param array $fieldIds
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
