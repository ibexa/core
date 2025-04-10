<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\UserPreference;

use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreferenceSetStruct;

abstract class Gateway
{
    /**
     * Store UserPreference ValueObject in persistent storage.
     */
    abstract public function setUserPreference(UserPreferenceSetStruct $userPreferenceSetStruct): int;

    /**
     * Get UserPreference by its user ID and name.
     *
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function getUserPreferenceByUserIdAndName(int $userId, string $name): array;

    abstract public function countUserPreferences(int $userId): int;

    /**
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadUserPreferences(int $userId, int $offset = 0, int $limit = -1): array;
}
