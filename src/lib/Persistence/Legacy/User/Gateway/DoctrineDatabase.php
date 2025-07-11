<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\User\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\User;
use Ibexa\Contracts\Core\Persistence\User\UserTokenUpdateStruct;
use Ibexa\Core\FieldType\User\UserStorage\Gateway\DoctrineStorage;
use Ibexa\Core\Persistence\Legacy\User\Gateway;
use Ibexa\Core\Persistence\Legacy\User\Role\Gateway as RoleGateway;
use function time;

/**
 * User gateway implementation using the Doctrine database.
 *
 * @internal Gateway implementation is considered internal. Use Persistence User Handler instead.
 *
 * @see \Ibexa\Contracts\Core\Persistence\User\Handler
 */
final class DoctrineDatabase extends Gateway
{
    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    /** @var \Doctrine\DBAL\Platforms\AbstractPlatform */
    private $dbPlatform;

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->dbPlatform = $this->connection->getDatabasePlatform();
    }

    public function load(int $userId): array
    {
        $query = $this->getLoadUserQueryBuilder();
        $query
            ->where(
                $query->expr()->eq(
                    'u.contentobject_id',
                    $query->createPositionalParameter($userId, ParameterType::INTEGER)
                )
            );

        $statement = $query->executeQuery();

        return $statement->fetchAllAssociative();
    }

    public function loadByLogin(string $login): array
    {
        $query = $this->getLoadUserQueryBuilder();
        $expr = $query->expr();
        $query
            ->where(
                $expr->eq(
                    $this->dbPlatform->getLowerExpression('u.login'),
                    // Index is case in-sensitive, on some db's lowercase, so we lowercase $login
                    $query->createPositionalParameter(
                        mb_strtolower($login, 'UTF-8'),
                        ParameterType::STRING
                    )
                )
            );

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadByEmail(string $email): array
    {
        $query = $this->getLoadUserQueryBuilder();
        $query->where(
            $query->expr()->eq(
                'u.email',
                $query->createPositionalParameter($email, ParameterType::STRING)
            )
        );

        $statement = $query->executeQuery();

        return $statement->fetchAllAssociative();
    }

    public function loadUserByToken(string $hash): array
    {
        $query = $this->getLoadUserQueryBuilder();
        $query
            ->leftJoin(
                'u',
                Gateway::USER_ACCOUNTKEY_TABLE,
                'token',
                $query->expr()->eq(
                    'token.user_id',
                    'u.contentobject_id'
                )
            )
            ->where(
                $query->expr()->eq(
                    'token.hash_key',
                    $query->createPositionalParameter($hash, ParameterType::STRING)
                )
            )
            ->andWhere(
                $query->expr()->gte(
                    'token.time',
                    $query->createPositionalParameter(time(), ParameterType::INTEGER)
                )
            );

        $statement = $query->executeQuery();

        return $statement->fetchAllAssociative();
    }

    public function updateUserPassword(User $user): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->update($this->connection->quoteIdentifier(self::USER_TABLE))
            ->set('password_hash', ':passwordHash')
            ->set('password_hash_type', ':passwordHashType')
            ->set('password_updated_at', ':passwordUpdatedAt')
            ->setParameter('passwordHash', $user->passwordHash, ParameterType::STRING)
            ->setParameter('passwordHashType', $user->hashAlgorithm, ParameterType::INTEGER)
            ->setParameter('passwordUpdatedAt', $user->passwordUpdatedAt)
            ->where(
                $queryBuilder->expr()->eq(
                    $this->connection->quoteIdentifier('contentobject_id'),
                    ':userId'
                )
            )
            ->setParameter('userId', $user->id, ParameterType::INTEGER);

        $queryBuilder->executeStatement();
    }

    public function updateUserToken(UserTokenUpdateStruct $userTokenUpdateStruct): void
    {
        $query = $this->connection->createQueryBuilder();
        if (false === $this->userHasToken($userTokenUpdateStruct->userId)) {
            $query
                ->insert(Gateway::USER_ACCOUNTKEY_TABLE)
                ->values(
                    [
                        'hash_key' => ':hash_key',
                        'time' => ':time',
                        'user_id' => ':user_id',
                    ]
                );
        } else {
            $query
                ->update(Gateway::USER_ACCOUNTKEY_TABLE)
                ->set('hash_key', ':hash_key')
                ->set('time', ':time')
                ->where('user_id = :user_id');
        }

        $query->setParameter('hash_key', $userTokenUpdateStruct->hashKey, ParameterType::STRING);
        $query->setParameter('time', $userTokenUpdateStruct->time, ParameterType::INTEGER);
        $query->setParameter('user_id', $userTokenUpdateStruct->userId, ParameterType::INTEGER);

        $query->executeStatement();
    }

    public function expireUserToken(string $hash): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(Gateway::USER_ACCOUNTKEY_TABLE)
            ->set(
                'time',
                $query->createPositionalParameter(0, ParameterType::INTEGER)
            )->where(
                $query->expr()->eq(
                    'hash_key',
                    $query->createPositionalParameter($hash, ParameterType::STRING)
                )
            );
        $query->executeStatement();
    }

    public function assignRole(int $contentId, int $roleId, array $limitation): void
    {
        foreach ($limitation as $identifier => $values) {
            foreach ($values as $value) {
                $query = $this->connection->createQueryBuilder();
                $query
                    ->insert(RoleGateway::USER_ROLE_TABLE)
                    ->values(
                        [
                            'contentobject_id' => $query->createPositionalParameter(
                                $contentId,
                                ParameterType::INTEGER
                            ),
                            'role_id' => $query->createPositionalParameter(
                                $roleId,
                                ParameterType::INTEGER
                            ),
                            'limit_identifier' => $query->createPositionalParameter(
                                $identifier,
                                ParameterType::STRING
                            ),
                            'limit_value' => $query->createPositionalParameter(
                                $value,
                                ParameterType::STRING
                            ),
                        ]
                    );
                $query->executeStatement();
            }
        }
    }

    public function removeRole(int $contentId, int $roleId): void
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->delete(RoleGateway::USER_ROLE_TABLE)
            ->where(
                $expr->eq(
                    'contentobject_id',
                    $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq(
                    'role_id',
                    $query->createPositionalParameter($roleId, ParameterType::INTEGER)
                )
            );
        $query->executeStatement();
    }

    public function removeRoleAssignmentById(int $roleAssignmentId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(RoleGateway::USER_ROLE_TABLE)
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($roleAssignmentId, ParameterType::INTEGER)
                )
            );
        $query->executeStatement();
    }

    private function getLoadUserQueryBuilder(): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select(
                'u.contentobject_id',
                'u.login',
                'u.email',
                'u.password_hash',
                'u.password_hash_type',
                'u.password_updated_at',
                's.is_enabled',
                's.max_login'
            )
            ->from(Gateway::USER_TABLE, 'u')
            ->leftJoin(
                'u',
                DoctrineStorage::USER_SETTING_TABLE,
                's',
                $expr->eq(
                    's.user_id',
                    'u.contentobject_id'
                )
            );

        return $query;
    }

    private function userHasToken(int $userId): bool
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('token.id')
            ->from(Gateway::USER_ACCOUNTKEY_TABLE, 'token')
            ->where(
                $expr->eq(
                    'token.user_id',
                    $query->createPositionalParameter(
                        $userId,
                        ParameterType::INTEGER
                    )
                )
            );

        return !empty($query->executeQuery()->fetch(FetchMode::ASSOCIATIVE));
    }
}
