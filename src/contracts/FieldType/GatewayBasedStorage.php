<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\FieldType;

use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

/**
 * Field Type External Storage gateway base class.
 *
 * @template T of \Ibexa\Contracts\Core\FieldType\StorageGatewayInterface
 */
abstract class GatewayBasedStorage implements FieldStorage
{
    /**
     * Field Type External Storage Gateway.
     *
     * @phpstan-var T
     */
    protected StorageGatewayInterface $gateway;

    /**
     * @param \Ibexa\Contracts\Core\FieldType\StorageGatewayInterface $gateway
     *
     * @phpstan-param T $gateway
     */
    public function __construct(StorageGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * This method is used exclusively by Legacy Storage to copy external data of existing field in main language to
     * the untranslatable field not passed in create or update struct, but created implicitly in storage layer.
     *
     * By default the method falls back to the {@see \Ibexa\Contracts\Core\FieldType\FieldStorage::storeFieldData()}.
     * External storages implement this method as needed.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $originalField
     *
     * @return bool|null Same as {@see \Ibexa\Contracts\Core\FieldType\FieldStorage::storeFieldData()}.
     */
    public function copyLegacyField(VersionInfo $versionInfo, Field $field, Field $originalField)
    {
        return $this->storeFieldData($versionInfo, $field);
    }
}
