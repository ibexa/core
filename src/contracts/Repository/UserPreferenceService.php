<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\UserPreference\UserPreference;
use Ibexa\Contracts\Core\Repository\Values\UserPreference\UserPreferenceList;
use Ibexa\Contracts\Core\Repository\Values\UserPreference\UserPreferenceSetStruct;

/**
 * User Preference Service.
 *
 * This service provides methods for managing user preferences. It works in the context of a current User (obtained from the PermissionResolver).
 */
interface UserPreferenceService
{
    /**
     * Set user preference.
     *
     * @param UserPreferenceSetStruct[] $userPreferenceSetStructs
     *
     * @throws UnauthorizedException If the current user user is not allowed to set user preference
     * @throws InvalidArgumentException If the $userPreferenceSetStruct is invalid
     */
    public function setUserPreference(array $userPreferenceSetStructs): void;

    /**
     * Get currently logged user preference by key.
     *
     * @param string $userPreferenceName
     *
     * @return UserPreference
     *
     * @throws UnauthorizedException if the current user user is not allowed to fetch user preference
     * @throws NotFoundException
     */
    public function getUserPreference(string $userPreferenceName): UserPreference;

    /**
     * Get currently logged user preferences.
     *
     * @param int $offset the start offset for paging
     * @param int $limit the number of user preferences returned
     *
     * @throws InvalidArgumentException
     *
     * @return UserPreferenceList
     */
    public function loadUserPreferences(
        int $offset = 0,
        int $limit = 25
    ): UserPreferenceList;

    /**
     * Get count of total preferences for currently logged user.
     *
     * @return int
     */
    public function getUserPreferenceCount(): int;
}
