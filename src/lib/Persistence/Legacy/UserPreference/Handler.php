<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\UserPreference;

use Ibexa\Contracts\Core\Persistence\UserPreference\Handler as HandlerInterface;
use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreference;
use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreferenceSetStruct;
use Ibexa\Core\Base\Exceptions\NotFoundException;

class Handler implements HandlerInterface
{
    /** @var Gateway */
    protected $gateway;

    /** @var Mapper */
    protected $mapper;

    /**
     * @param Gateway $gateway
     * @param Mapper $mapper
     */
    public function __construct(
        Gateway $gateway,
        Mapper $mapper
    ) {
        $this->gateway = $gateway;
        $this->mapper = $mapper;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotFoundException
     */
    public function setUserPreference(UserPreferenceSetStruct $setStruct): UserPreference
    {
        $this->gateway->setUserPreference($setStruct);

        return $this->getUserPreferenceByUserIdAndName($setStruct->userId, $setStruct->name);
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotFoundException
     */
    public function getUserPreferenceByUserIdAndName(
        int $userId,
        string $name
    ): UserPreference {
        $userPreference = $this->mapper->extractUserPreferencesFromRows(
            $this->gateway->getUserPreferenceByUserIdAndName($userId, $name)
        );

        if (count($userPreference) < 1) {
            throw new NotFoundException('User Preference', $userId . ',' . $name);
        }

        return reset($userPreference);
    }

    /**
     * {@inheritdoc}
     */
    public function countUserPreferences(int $userId): int
    {
        return $this->gateway->countUserPreferences($userId);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserPreferences(
        int $userId,
        int $offset,
        int $limit
    ): array {
        return $this->mapper->extractUserPreferencesFromRows(
            $this->gateway->loadUserPreferences($userId, $offset, $limit)
        );
    }
}
