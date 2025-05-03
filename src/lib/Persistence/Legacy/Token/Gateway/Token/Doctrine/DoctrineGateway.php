<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Token\Gateway\Token\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Core\Base\Exceptions\NotFoundException as NotFound;
use Ibexa\Core\Persistence\Legacy\Token\AbstractGateway;
use Ibexa\Core\Persistence\Legacy\Token\Gateway\Token\Gateway;
use Ibexa\Core\Persistence\Legacy\Token\Gateway\TokenType\Doctrine\DoctrineGateway as TokenTypeGateway;

/**
 * @internal
 */
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
    public const COLUMN_REVOKED = 'revoked';

    public const TOKEN_SEQ = 'ibexa_token_id_seq';

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
            self::COLUMN_REVOKED,
        ];
    }

    public function insert(
        int $typeId,
        string $token,
        ?string $identifier,
        int $ttl
    ): int {
        $now = $this->getCurrentUnixTimestamp();
        $this->connection->insert(
            self::TABLE_NAME,
            [
                self::COLUMN_TYPE_ID => $typeId,
                self::COLUMN_TOKEN => $token,
                self::COLUMN_IDENTIFIER => $identifier,
                self::COLUMN_CREATED => $now,
                self::COLUMN_EXPIRES => $now + $ttl,
                self::COLUMN_REVOKED => false,
            ],
            [
                self::COLUMN_TYPE_ID => ParameterType::INTEGER,
                self::COLUMN_CREATED => ParameterType::INTEGER,
                self::COLUMN_EXPIRES => ParameterType::INTEGER,
                self::COLUMN_REVOKED => ParameterType::BOOLEAN,
            ]
        );

        return (int)$this->connection->lastInsertId(self::TOKEN_SEQ);
    }

    public function revoke(int $tokenId): void
    {
        $this->connection->update(
            self::TABLE_NAME,
            [
                self::COLUMN_REVOKED => true,
            ],
            [
                self::COLUMN_ID => $tokenId,
            ],
            [
                self::COLUMN_REVOKED => ParameterType::BOOLEAN,
            ]
        );
    }

    public function revokeByIdentifier(int $typeId, ?string $identifier): void
    {
        $this->connection->update(
            self::TABLE_NAME,
            [
                self::COLUMN_REVOKED => true,
            ],
            [
                self::COLUMN_TYPE_ID => $typeId,
                self::COLUMN_IDENTIFIER => $identifier,
            ],
            [
                self::COLUMN_REVOKED => ParameterType::BOOLEAN,
            ]
        );
    }

    public function delete(int $tokenId): void
    {
        $this->connection->delete(
            self::TABLE_NAME,
            [
                self::COLUMN_ID => $tokenId,
            ],
            [
                self::COLUMN_ID => ParameterType::INTEGER,
            ]
        );
    }

    public function deleteExpired(?int $typeId = null): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::TABLE_NAME)
            ->andWhere(
                $query->expr()->lt(self::COLUMN_EXPIRES, ':now')
            )
            ->setParameter('now', $this->getCurrentUnixTimestamp(), ParameterType::INTEGER);

        if (null !== $typeId) {
            $query->andWhere(
                $query->expr()->eq(
                    self::COLUMN_TYPE_ID,
                    ':type_id'
                )
            );
            $query->setParameter('type_id', $typeId, ParameterType::INTEGER);
        }

        $query->executeStatement();
    }

    public function getToken(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ): array {
        $query = $this->getTokenSelectQueryBuilder($tokenType, $token, $identifier);
        $row = $query->executeQuery()->fetchAssociative();

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
                    ':token_id'
                )
            );

        $query->setParameter('token_id', $tokenId, ParameterType::INTEGER);

        $row = $query->executeQuery()->fetchAssociative();

        if (false === $row) {
            throw new NotFound('token', "id: $tokenId");
        }

        return $row;
    }

    private function getCurrentUnixTimestamp(): int
    {
        return time();
    }

    private function getTokenSelectQueryBuilder(
        string $tokenType,
        string $token,
        ?string $identifier = null
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
                    ':token_type'
                )
            );

        $query->setParameter('token_type', $tokenType, ParameterType::STRING);
        $query->setParameter('token', $token, ParameterType::STRING);

        if (null !== $identifier) {
            $query->andWhere(
                $query->expr()->eq(
                    $this->getAliasedColumn(self::COLUMN_IDENTIFIER, self::DEFAULT_TABLE_ALIAS),
                    ':identifier'
                )
            );
            $query->setParameter('identifier', $identifier, ParameterType::STRING);
        }

        return $query;
    }
}
