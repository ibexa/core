<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Token\Gateway\TokenType\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Core\Base\Exceptions\NotFoundException as NotFound;
use Ibexa\Core\Persistence\Legacy\Token\AbstractGateway;
use Ibexa\Core\Persistence\Legacy\Token\Gateway\TokenType\Gateway;

/**
 * @internal
 */
final class DoctrineGateway extends AbstractGateway implements Gateway
{
    public const TABLE_NAME = 'ibexa_token_type';
    public const DEFAULT_TABLE_ALIAS = 'token_type';

    public const COLUMN_ID = 'id';
    public const COLUMN_IDENTIFIER = 'identifier';
    public const TOKEN_TYPE_SEQ = 'ibexa_token_type_id_seq';

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
        $this->connection->insert(self::TABLE_NAME, [
            self::COLUMN_IDENTIFIER => $identifier,
        ]);

        return (int)$this->connection->lastInsertId(self::TOKEN_TYPE_SEQ);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteById(int $typeId): void
    {
        $this->connection->delete(self::TABLE_NAME, [
            self::COLUMN_ID => $typeId,
        ]);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteByIdentifier(string $identifier): void
    {
        $this->connection->delete(self::TABLE_NAME, [
            self::COLUMN_IDENTIFIER => $identifier,
        ]);
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
                    ':type_id'
                )
            );

        $query->setParameter('type_id', $typeId, ParameterType::INTEGER);

        $row = $query->executeQuery()->fetchAssociative();

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

        $query->setParameter('identifier', $identifier, ParameterType::STRING);

        $row = $query->executeQuery()->fetchAssociative();

        if (false === $row) {
            throw new NotFound('token_type', "identifier: $identifier");
        }

        return $row;
    }
}
