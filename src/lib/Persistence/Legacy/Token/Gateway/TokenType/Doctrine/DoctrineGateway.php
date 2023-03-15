<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Token\Gateway\TokenType\Doctrine;

use Doctrine\DBAL\Connection;
use Ibexa\Core\Base\Exceptions\NotFoundException as NotFound;
use Ibexa\Core\Persistence\Legacy\Token\AbstractGateway;
use Ibexa\Core\Persistence\Legacy\Token\Gateway\TokenType\Gateway;
use PDO;

final class DoctrineGateway extends AbstractGateway implements Gateway
{
    public const TABLE_NAME = 'ibexa_token_type';
    public const DEFAULT_TABLE_ALIAS = 'token_type';

    public const COLUMN_ID = 'id';
    public const COLUMN_IDENTIFIER = 'identifier';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public static function getColumns(): array
    {
        return [
            self::COLUMN_ID,
            self::COLUMN_IDENTIFIER,
        ];
    }

    public function insert(string $identifier): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::TABLE_NAME)
            ->values([self::COLUMN_IDENTIFIER => ':identifier'])
            ->setParameter(':identifier', $identifier, PDO::PARAM_STR);

        $query->execute();

        return (int)$this->connection->lastInsertId();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteById(int $typeId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::TABLE_NAME)
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'))
            ->setParameter(':id', $typeId, PDO::PARAM_INT);

        $query->execute();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteByIdentifier(string $identifier): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::TABLE_NAME)
            ->where($query->expr()->eq(self::COLUMN_IDENTIFIER, ':identifier'))
            ->setParameter(':identifier', $identifier, PDO::PARAM_STR);

        $query->execute();
    }

    public function getTypeById(int $typeId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getAliasedColumns(self::DEFAULT_TABLE_ALIAS, self::getColumns()))
            ->from(self::TABLE_NAME, self::DEFAULT_TABLE_ALIAS)
            ->andWhere(
                $query->expr()->eq(
                    $this->getAliasedColumn(self::COLUMN_ID, self::DEFAULT_TABLE_ALIAS),
                    ':typeId'
                )
            );

        $query->setParameter(':typeId', $typeId, PDO::PARAM_INT);

        $row = $query->execute()->fetchAssociative();

        if (false === $row) {
            throw new NotFound('token_type', "id: $typeId");
        }

        return $row;
    }

    public function getTypeByIdentifier(string $identifier): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getAliasedColumns(self::DEFAULT_TABLE_ALIAS, self::getColumns()))
            ->from(self::TABLE_NAME, self::DEFAULT_TABLE_ALIAS)
            ->andWhere(
                $query->expr()->eq(
                    $this->getAliasedColumn(self::COLUMN_IDENTIFIER, self::DEFAULT_TABLE_ALIAS),
                    ':identifier'
                )
            );

        $query->setParameter(':identifier', $identifier, PDO::PARAM_STR);

        $row = $query->execute()->fetchAssociative();

        if (false === $row) {
            throw new NotFound('token_type', "identifier: $identifier");
        }

        return $row;
    }
}
