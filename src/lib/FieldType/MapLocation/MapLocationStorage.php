<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\MapLocation;

use Ibexa\Contracts\Core\FieldType\GatewayBasedStorage;
use Ibexa\Contracts\Core\FieldType\StorageGatewayInterface;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\FieldType\MapLocation\MapLocationStorage\Gateway;

/**
 * Storage for the MapLocation field type.
 */
class MapLocationStorage extends GatewayBasedStorage
{
    /** @var Gateway */
    protected StorageGatewayInterface $gateway;

    public function storeFieldData(
        VersionInfo $versionInfo,
        Field $field
    ) {
        return $this->gateway->storeFieldData($versionInfo, $field);
    }

    public function getFieldData(
        VersionInfo $versionInfo,
        Field $field
    ) {
        $this->gateway->getFieldData($versionInfo, $field);
    }

    public function deleteFieldData(
        VersionInfo $versionInfo,
        array $fieldIds
    ) {
        $this->gateway->deleteFieldData($versionInfo, $fieldIds);
    }

    public function hasFieldData(): bool
    {
        return true;
    }
}
