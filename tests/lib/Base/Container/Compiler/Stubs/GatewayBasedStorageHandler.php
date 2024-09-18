<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Base\Container\Compiler\Stubs;

use Ibexa\Contracts\Core\FieldType\GatewayBasedStorage;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

/**
 * Stub implementation of GatewayBasedStorage.
 */
class GatewayBasedStorageHandler extends GatewayBasedStorage
{
    public function storeFieldData(VersionInfo $versionInfo, Field $field)
    {
    }

    public function getFieldData(VersionInfo $versionInfo, Field $field)
    {
    }

    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds)
    {
    }

    public function hasFieldData()
    {
    }
}
