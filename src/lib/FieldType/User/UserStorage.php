<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\User;

use Doctrine\DBAL\DBALException;
use Ibexa\Contracts\Core\FieldType\GatewayBasedStorage;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\FieldType\User\UserStorage\Gateway;

/**
 * Description of UserStorage.
 *
 * Methods in this interface are called by storage engine.
 * Proper Gateway and its Connection is injected via Dependency Injection.
 *
 * The User storage handles the following attributes, following the user field
 * type in Ibexa 4:
 *  - account_key
 *  - has_stored_login
 *  - is_enabled
 *  - is_locked
 *  - last_visit
 *  - login_count
 */
class UserStorage extends GatewayBasedStorage
{
    /**
     * Field Type External Storage Gateway.
     *
     * @var Gateway
     */
    protected $gateway;

    public function storeFieldData(
        VersionInfo $versionInfo,
        Field $field,
        array $context
    ) {
        return $this->gateway->storeFieldData($versionInfo, $field);
    }

    public function getFieldData(
        VersionInfo $versionInfo,
        Field $field,
        array $context
    ) {
        $field->value->externalData = $this->gateway->getFieldData($field->id);
    }

    /**
     * @param VersionInfo $versionInfo
     * @param int[] $fieldIds Array of field Ids
     * @param array $context
     *
     * @return bool
     *
     * @throws DBALException
     */
    public function deleteFieldData(
        VersionInfo $versionInfo,
        array $fieldIds,
        array $context
    ) {
        return $this->gateway->deleteFieldData($versionInfo, $fieldIds);
    }

    /**
     * Checks if field type has external data to deal with.
     *
     * @return bool
     */
    public function hasFieldData()
    {
        return true;
    }

    /**
     * @param VersionInfo $versionInfo
     * @param Field $field
     * @param array $context
     *
     * @return \Ibexa\Contracts\Core\Search\Field[]
     */
    public function getIndexData(
        VersionInfo $versionInfo,
        Field $field,
        array $context
    ) {}

    /**
     * @param int[] $supportedHashTypes
     */
    public function countUsersWithUnsupportedHashType(array $supportedHashTypes): int
    {
        return $this->gateway->countUsersWithUnsupportedHashType($supportedHashTypes);
    }
}

class_alias(UserStorage::class, 'eZ\Publish\Core\FieldType\User\UserStorage');
