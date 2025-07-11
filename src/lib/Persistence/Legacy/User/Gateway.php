<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\User;

use Ibexa\Contracts\Core\Persistence\User;
use Ibexa\Contracts\Core\Persistence\User\UserTokenUpdateStruct;

/**
 * User Gateway.
 *
 * @internal For internal use by Persistence Handlers.
 */
abstract class Gateway
{
    public const USER_TABLE = 'ibexa_user';

    public const string USER_ACCOUNTKEY_TABLE = 'ibexa_user_accountkey';

    /**
     * Load a User by User ID.
     */
    abstract public function load(int $userId): array;

    /**
     * Load a User by User login.
     */
    abstract public function loadByLogin(string $login): array;

    /**
     * Load a User by User e-mail.
     */
    abstract public function loadByEmail(string $email): array;

    /**
     * Load a User by User token.
     */
    abstract public function loadUserByToken(string $hash): array;

    /**
     * Update the user password as specified by the user struct.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User $user
     */
    abstract public function updateUserPassword(User $user): void;

    /**
     * Update a User token specified by UserTokenUpdateStruct.
     *
     * @see \Ibexa\Contracts\Core\Persistence\User\UserTokenUpdateStruct
     */
    abstract public function updateUserToken(UserTokenUpdateStruct $userTokenUpdateStruct): void;

    /**
     * Expire the given User token.
     */
    abstract public function expireUserToken(string $hash): void;

    /**
     * Assign, with the given Limitation, a Role to a User.
     *
     * @param array $limitation a map of the Limitation identifiers to raw Limitation values.
     */
    abstract public function assignRole(int $contentId, int $roleId, array $limitation): void;

    /**
     * Remove a Role from User or User group.
     */
    abstract public function removeRole(int $contentId, int $roleId): void;

    /**
     * Remove a Role from User or User group, by assignment ID.
     */
    abstract public function removeRoleAssignmentById(int $roleAssignmentId): void;
}
