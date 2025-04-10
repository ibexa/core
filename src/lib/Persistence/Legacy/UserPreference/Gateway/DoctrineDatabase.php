<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\UserPreference\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreferenceSetStruct;
use Ibexa\Core\Persistence\Legacy\UserPreference\Gateway;

class DoctrineDatabase extends Gateway
{
    public const string TABLE_USER_PREFERENCES = 'ezpreferences';

    public const string COLUMN_ID = 'id';
    public const string COLUMN_NAME = 'name';
    public const string COLUMN_USER_ID = 'user_id';
    public const string COLUMN_VALUE = 'value';
    private const string VALUE_PARAM_NAME = 'value';
    private const string ID_PARAM_NAME = 'id';
    private const string NAME_PARAM_NAME = 'name';
    private const string USER_ID_PARAM_NAME = 'user_id';

    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function setUserPreference(UserPreferenceSetStruct $userPreferenceSetStruct): int
    {
        $query = $this->connection->createQueryBuilder();

        $userPreferences = $this->getUserPreferenceByUserIdAndName(
            $userPreferenceSetStruct->userId,
            $userPreferenceSetStruct->name
        );

        if (0 < count($userPreferences)) {
            $currentUserPreference = reset($userPreferences);
            $currentUserPreferenceId = (int)$currentUserPreference['id'];

            $query
                ->update(self::TABLE_USER_PREFERENCES)
                ->set(self::COLUMN_VALUE, ':' . self::VALUE_PARAM_NAME)
                ->where($query->expr()->eq(self::COLUMN_ID, ':' . self::ID_PARAM_NAME))
                ->setParameter(self::ID_PARAM_NAME, $currentUserPreferenceId, ParameterType::INTEGER)
                ->setParameter(self::VALUE_PARAM_NAME, $userPreferenceSetStruct->value);

            $query->executeStatement();

            return $currentUserPreferenceId;
        }

        $query
            ->insert(self::TABLE_USER_PREFERENCES)
            ->values([
                self::COLUMN_NAME => ':' . self::NAME_PARAM_NAME,
                self::COLUMN_USER_ID => ':' . self::USER_ID_PARAM_NAME,
                self::COLUMN_VALUE => ':' . self::VALUE_PARAM_NAME,
            ])
            ->setParameter(self::NAME_PARAM_NAME, $userPreferenceSetStruct->name)
            ->setParameter(self::USER_ID_PARAM_NAME, $userPreferenceSetStruct->userId, ParameterType::INTEGER)
            ->setParameter(self::VALUE_PARAM_NAME, $userPreferenceSetStruct->value);

        $query->executeStatement();

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getUserPreferenceByUserIdAndName(int $userId, string $name): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_USER_PREFERENCES)
            ->where($query->expr()->eq(self::COLUMN_USER_ID, ':userId'))
            ->andWhere($query->expr()->eq(self::COLUMN_NAME, ':' . self::NAME_PARAM_NAME));

        $query->setParameter('userId', $userId, ParameterType::INTEGER);
        $query->setParameter(self::NAME_PARAM_NAME, $name);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadUserPreferences(int $userId, int $offset = 0, int $limit = -1): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_USER_PREFERENCES)
            ->where($query->expr()->eq(self::COLUMN_USER_ID, ':' . self::USER_ID_PARAM_NAME))
            ->setFirstResult($offset);

        if ($limit > 0) {
            $query->setMaxResults($limit);
        }

        $query->orderBy(self::COLUMN_ID, 'ASC');
        $query->setParameter(self::USER_ID_PARAM_NAME, $userId, ParameterType::INTEGER);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function countUserPreferences(int $userId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                'COUNT(self::COLUMN_ID)'
            )
            ->from(self::TABLE_USER_PREFERENCES)
            ->where($query->expr()->eq(self::COLUMN_USER_ID, ':' . self::USER_ID_PARAM_NAME))
            ->setParameter(self::USER_ID_PARAM_NAME, $userId, ParameterType::INTEGER);

        return (int) $query->executeQuery()->fetchOne();
    }

    /**
     * @return string[]
     */
    private function getColumns(): array
    {
        return [
            self::COLUMN_ID,
            self::COLUMN_NAME,
            self::COLUMN_USER_ID,
            self::COLUMN_VALUE,
        ];
    }
}
