<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Token\Gateway\Token\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Core\Base\Exceptions\NotFoundException as NotFound;
use Ibexa\Core\Persistence\Legacy\Token\AbstractGateway;
use Ibexa\Core\Persistence\Legacy\Token\Gateway\Token\Gateway;
use Ibexa\Core\Persistence\Legacy\Token\Gateway\TokenType\Doctrine\DoctrineGateway as TokenTypeGateway;
use PDO;

final class DoctrineGateway extends AbstractGateway implements Gateway
{
    public const TABLE_NAME = 'ibexa_token';
    public const DEFAULT_TABLE_ALIAS = 'token';

    public const COLUMN_ID = 'id';
    public const COLUMN_TYPE_ID = 'type_id';
    public const COLUMN_TOKEN = 'token';
    public const COLUMN_IDENTIFIER = 'identifier';
    public const COLUMN_CREATED = 'created';
    public const COLUMN_EXPIRES = 'expires';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public static function getColumns(): array
    {
        return [
            self::COLUMN_ID,
            self::COLUMN_TYPE_ID,
            self::COLUMN_TOKEN,
            self::COLUMN_IDENTIFIER,
            self::COLUMN_CREATED,
            self::COLUMN_EXPIRES,
        ];
    }

    public function insert(
        int $typeId,
        string $token,
        ?string $identifier,
        int $ttl
    ): int {
        $now = time();
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::TABLE_NAME)
            ->values([
                self::COLUMN_TYPE_ID => ':type_id',
                self::COLUMN_TOKEN => ':token',
                self::COLUMN_IDENTIFIER => ':identifier',
                self::COLUMN_CREATED => ':created',
                self::COLUMN_EXPIRES => ':expires',
            ])
            ->setParameter(':type_id', $typeId, PDO::PARAM_INT)
            ->setParameter(':token', $token, PDO::PARAM_STR)
            ->setParameter(':identifier', $identifier, PDO::PARAM_STR)
            ->setParameter(':created', $now, PDO::PARAM_INT)
            ->setParameter(':expires', $now + $ttl, PDO::PARAM_INT);

        $query->execute();

        return (int)$this->connection->lastInsertId();
    }

    public function delete(int $tokenId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::TABLE_NAME)
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'))
            ->setParameter(':id', $tokenId, PDO::PARAM_INT);

        $query->execute();
    }

    public function deleteExpired(?int $typeId = null): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::TABLE_NAME)
            ->andWhere(
                $query->expr()->lt(self::COLUMN_EXPIRES, ':now')
            )
            ->setParameter(':now', time(), PDO::PARAM_INT);

        if (!empty($typeId)) {
            $query->andWhere(
                $query->expr()->eq(
                    self::COLUMN_TYPE_ID,
                    ':type_id'
                )
            );
            $query->setParameter(':type_id', $typeId, PDO::PARAM_INT);
        }

        $query->execute();
    }

    public function getToken(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ): array {
        $query = $this->getTokenSelectQueryBuilder($tokenType, $token, $identifier);
        $row = $query->execute()->fetchAssociative();

        if (false === $row) {
            throw new NotFound('token', "token: $token, type: $tokenType, identifier: $identifier");
        }

        return $row;
    }

    public function getTokenById(int $tokenId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getAliasedColumns(self::DEFAULT_TABLE_ALIAS, self::getColumns()))
            ->from(self::TABLE_NAME, self::DEFAULT_TABLE_ALIAS)
            ->andWhere(
                $query->expr()->eq(
                    $this->getAliasedColumn(self::COLUMN_ID, self::DEFAULT_TABLE_ALIAS),
                    ':tokenId'
                )
            );

        $query->setParameter(':tokenId', $tokenId, PDO::PARAM_INT);

        $row = $query->execute()->fetchAssociative();

        if (false === $row) {
            throw new NotFound('token', "id: $tokenId");
        }

        return $row;
    }

    private function getTokenSelectQueryBuilder(
        string $tokenType,
        string $token,
        ?string $identifier = null,
        bool $externalIdentifier = false
    ): QueryBuilder {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select(...$this->getAliasedColumns(self::DEFAULT_TABLE_ALIAS, self::getColumns()))
            ->from(self::TABLE_NAME, self::DEFAULT_TABLE_ALIAS)
            ->innerJoin(
                self::DEFAULT_TABLE_ALIAS,
                TokenTypeGateway::TABLE_NAME,
                TokenTypeGateway::DEFAULT_TABLE_ALIAS,
                $expr->eq(
                    $this->getAliasedColumn(self::COLUMN_TYPE_ID, self::DEFAULT_TABLE_ALIAS),
                    $this->getAliasedColumn(
                        TokenTypeGateway::COLUMN_ID,
                        TokenTypeGateway::DEFAULT_TABLE_ALIAS
                    )
                )
            )
            ->andWhere(
                $query->expr()->eq(
                    $this->getAliasedColumn(self::COLUMN_TOKEN, self::DEFAULT_TABLE_ALIAS),
                    ':token'
                )
            )
            ->andWhere(
                $query->expr()->eq(
                    $this->getAliasedColumn(
                        TokenTypeGateway::COLUMN_IDENTIFIER,
                        TokenTypeGateway::DEFAULT_TABLE_ALIAS
                    ),
                    ':tokenType'
                )
            );

        $query->setParameter(':tokenType', $tokenType, PDO::PARAM_STR);
        $query->setParameter(':token', $token, PDO::PARAM_STR);

        if (!$externalIdentifier && null !== $identifier) {
            $query->andWhere(
                $query->expr()->eq(
                    $this->getAliasedColumn(self::COLUMN_IDENTIFIER, self::DEFAULT_TABLE_ALIAS),
                    ':identifier'
                )
            );
            $query->setParameter(':identifier', $identifier, PDO::PARAM_STR);
        }

        return $query;
    }
}
