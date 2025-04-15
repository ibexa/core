<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\User\UserStorage;

use Ibexa\Contracts\Core\FieldType\StorageGateway;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

abstract class Gateway extends StorageGateway
{
    /**
     * Get field data.
     *
     * The User storage handles the following attributes, following the user field
     * type in Ibexa:
     * - account_key
     * - has_stored_login
     * - contentobject_id
     * - login
     * - email
     * - password_hash
     * - password_hash_type
     * - password_updated_at
     * - is_enabled
     * - is_locked
     * - last_visit
     * - login_count
     * - max_login
     *
     * @return array{
     *     hasStoredLogin: bool,
     *     contentId: int|null,
     *     login: string|null,
     *     email: string|null,
     *     passwordHash: string|null,
     *     passwordHashType: string|null,
     *     passwordUpdatedAt: int|null,
     *     enabled: bool,
     *     maxLogin: int|null
     * }
     */
    abstract public function getFieldData(int $fieldId, ?int $userId = null): array;

    abstract public function storeFieldData(VersionInfo $versionInfo, Field $field): bool;

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param int[] $fieldIds
     *
     * @return bool
     *
     * @throws \Doctrine\DBAL\Exception
     */
    abstract public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds): bool;

    /**
     * @param int[] $supportedHashTypes
     */
    abstract public function countUsersWithUnsupportedHashType(array $supportedHashTypes): int;
}
