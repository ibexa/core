<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Setting\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Core\Persistence\Legacy\Setting\Gateway;

/**
 * @internal Gateway implementation is considered internal. Use Persistence Setting Handler instead.
 *
 * @see \Ibexa\Contracts\Core\Persistence\Setting\Handler
 */
final class DoctrineDatabase extends Gateway
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function insertSetting(string $group, string $identifier, string $serializedValue): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::SETTING_TABLE)
            ->values(
                [
                    $this->connection->quoteIdentifier('group') => $query->createPositionalParameter($group),
                    'identifier' => $query->createPositionalParameter($identifier),
                    'value' => $query->createPositionalParameter($serializedValue),
                ]
            );

        $query->executeStatement();

        return (int)$this->connection->lastInsertId(Gateway::SETTING_SEQ);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateSetting(string $group, string $identifier, string $serializedValue): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::SETTING_TABLE)
            ->set('value', $query->createPositionalParameter($serializedValue))
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('group'),
                    $query->createPositionalParameter($group)
                ),
                $query->expr()->eq(
                    'identifier',
                    $query->createPositionalParameter($identifier)
                )
            );

        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadSetting(string $group, string $identifier): ?array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                $this->connection->quoteIdentifier('group'),
                'identifier',
                'value',
            )
            ->from(self::SETTING_TABLE)
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('group'),
                    $query->createPositionalParameter($group)
                ),
                $query->expr()->eq(
                    'identifier',
                    $query->createPositionalParameter($identifier)
                )
            );

        $result = $query->executeQuery()->fetchAssociative();

        if (false === $result) {
            return null;
        }

        return $result;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadSettingById(int $id): ?array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                $this->connection->quoteIdentifier('group'),
                'identifier',
                'value',
            )
            ->from(self::SETTING_TABLE)
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            );

        $result = $query->executeQuery()->fetchAssociative();

        if (false === $result) {
            return null;
        }

        return $result;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteSetting(string $group, string $identifier): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::SETTING_TABLE)
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('group'),
                    $query->createPositionalParameter($group)
                ),
                $query->expr()->eq(
                    'identifier',
                    $query->createPositionalParameter($identifier)
                )
            );

        $query->executeStatement();
    }
}
