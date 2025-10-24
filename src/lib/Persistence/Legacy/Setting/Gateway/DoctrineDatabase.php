<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Setting\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Setting\Handler;
use Ibexa\Core\Persistence\Legacy\Setting\Gateway;

/**
 * @internal Gateway implementation is considered internal. Use Persistence Setting Handler instead.
 *
 * @see Handler
 */
final class DoctrineDatabase extends Gateway
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function insertSetting(
        string $group,
        string $identifier,
        string $serializedValue
    ): int {
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

    public function updateSetting(
        string $group,
        string $identifier,
        string $serializedValue
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::SETTING_TABLE)
            ->set('value', $query->createPositionalParameter($serializedValue))
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('group'),
                    $query->createPositionalParameter($group, ParameterType::STRING)
                ),
                $query->expr()->eq(
                    'identifier',
                    $query->createPositionalParameter($identifier, ParameterType::STRING)
                )
            );

        $query->executeStatement();
    }

    public function loadSetting(
        string $group,
        string $identifier
    ): ?array {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                $this->connection->quoteIdentifier('group'),
                'identifier',
                'value'
            )
            ->from(self::SETTING_TABLE)
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('group'),
                    $query->createPositionalParameter($group, ParameterType::STRING)
                ),
                $query->expr()->eq(
                    'identifier',
                    $query->createPositionalParameter($identifier, ParameterType::STRING)
                )
            );

        $statement = $query->executeQuery();
        $result = $statement->fetchAssociative();

        if (false === $result) {
            return null;
        }

        return $result;
    }

    public function loadSettingById(int $id): ?array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                $this->connection->quoteIdentifier('group'),
                'identifier',
                'value'
            )
            ->from(self::SETTING_TABLE)
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            );

        $statement = $query->executeQuery();
        $result = $statement->fetchAssociative();

        if (false === $result) {
            return null;
        }

        return $result;
    }

    public function deleteSetting(
        string $group,
        string $identifier
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::SETTING_TABLE)
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('group'),
                    $query->createPositionalParameter($group, ParameterType::STRING)
                ),
                $query->expr()->eq(
                    'identifier',
                    $query->createPositionalParameter($identifier, ParameterType::STRING)
                )
            );

        $query->executeStatement();
    }
}
