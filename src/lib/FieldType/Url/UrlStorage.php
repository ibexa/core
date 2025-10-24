<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Url;

use Ibexa\Contracts\Core\FieldType\GatewayBasedStorage;
use Ibexa\Contracts\Core\FieldType\StorageGatewayInterface;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\FieldType\Url\UrlStorage\Gateway;
use Psr\Log\LoggerInterface;

/**
 * Converter for Url field type external storage.
 */
class UrlStorage extends GatewayBasedStorage
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var Gateway */
    protected StorageGatewayInterface $gateway;

    /**
     * Construct from gateways.
     *
     * @param StorageGatewayInterface $gateway
     * @param LoggerInterface $logger
     */
    public function __construct(
        StorageGatewayInterface $gateway,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($gateway);
        $this->logger = $logger;
    }

    public function storeFieldData(
        VersionInfo $versionInfo,
        Field $field
    ): bool {
        $url = $field->value->externalData;

        if (empty($url)) {
            return false;
        }

        $map = $this->gateway->getUrlIdMap([$url]);

        $urlId = $map[$url] ?? $this->gateway->insertUrl($url);

        $this->gateway->linkUrl($urlId, $field->id, $versionInfo->versionNo);

        $this->gateway->unlinkUrl(
            $field->id,
            $versionInfo->versionNo,
            [$urlId]
        );

        $field->value->data['urlId'] = $urlId;

        // Signals that the Value has been modified and that an update is to be performed
        return true;
    }

    public function getFieldData(
        VersionInfo $versionInfo,
        Field $field
    ) {
        $id = $field->value->data['urlId'];
        if (empty($id)) {
            $field->value->externalData = null;

            return;
        }

        $map = $this->gateway->getIdUrlMap([$id]);

        // URL id is not in the DB
        if (!isset($map[$id]) && isset($this->logger)) {
            $this->logger->error("URL with ID '{$id}' not found");
        }

        $field->value->externalData = isset($map[$id]) ? $map[$id] : '';
    }

    public function deleteFieldData(
        VersionInfo $versionInfo,
        array $fieldIds
    ) {
        foreach ($fieldIds as $fieldId) {
            $this->gateway->unlinkUrl($fieldId, $versionInfo->versionNo);
        }
    }

    /**
     * Checks if field type has external data to deal with.
     *
     * @return bool
     */
    public function hasFieldData(): bool
    {
        return true;
    }
}
