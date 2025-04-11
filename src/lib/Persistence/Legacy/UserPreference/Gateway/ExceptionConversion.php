<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\UserPreference\Gateway;

use Doctrine\DBAL\Exception as DBALException;
use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreferenceSetStruct;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Persistence\Legacy\UserPreference\Gateway;

class ExceptionConversion extends Gateway
{
    protected DoctrineDatabase $innerGateway;

    public function __construct(DoctrineDatabase $innerGateway)
    {
        $this->innerGateway = $innerGateway;
    }

    public function getUserPreferenceByUserIdAndName(int $userId, string $name): array
    {
        try {
            return $this->innerGateway->getUserPreferenceByUserIdAndName($userId, $name);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function countUserPreferences(int $userId): int
    {
        try {
            return $this->innerGateway->countUserPreferences($userId);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadUserPreferences(int $userId, int $offset = 0, int $limit = -1): array
    {
        try {
            return $this->innerGateway->loadUserPreferences($userId, $offset, $limit);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function setUserPreference(UserPreferenceSetStruct $userPreferenceSetStruct): int
    {
        try {
            return $this->innerGateway->setUserPreference($userPreferenceSetStruct);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }
}
