<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\User\UserStorage\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\Base\Exceptions\ForbiddenException;
use Ibexa\Core\FieldType\User\UserStorage\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\User\Gateway as UserGateway;
use PDO;

/**
 * User DoctrineStorage gateway.
 */
class DoctrineStorage extends Gateway
{
    public const string USER_TABLE = UserGateway::USER_TABLE;
    public const string USER_SETTING_TABLE = 'ibexa_user_setting';

    protected array $defaultValues = [
        'hasStoredLogin' => false,
        'contentId' => null,
        'login' => null,
        'email' => null,
        'passwordHash' => null,
        'passwordHashType' => null,
        'passwordUpdatedAt' => null,
        'enabled' => false,
        'maxLogin' => null,
    ];

    public function __construct(
        protected Connection $connection
    ) {
    }

    public function getFieldData($fieldId, $userId = null): array
    {
        $userId = $userId ?: $this->fetchUserId($fieldId);
        $userData = $this->fetchUserData($userId);

        if (!isset($userData['login'])) {
            return $this->defaultValues;
        }

        $result = array_merge(
            $this->defaultValues,
            [
                'hasStoredLogin' => true,
            ],
            $userData,
            $this->fetchUserSettings($userId)
        );

        return $result;
    }

    protected function getPropertyMap(): array
    {
        return [
            'has_stored_login' => [
                'name' => 'hasStoredlogin',
                'cast' => static function ($input): bool {
                    return $input == '1';
                },
            ],
            'contentobject_id' => [
                'name' => 'contentId',
                'cast' => 'intval',
            ],
            'login' => [
                'name' => 'login',
                'cast' => 'strval',
            ],
            'email' => [
                'name' => 'email',
                'cast' => 'strval',
            ],
            'password_hash' => [
                'name' => 'passwordHash',
                'cast' => 'strval',
            ],
            'password_hash_type' => [
                'name' => 'passwordHashType',
                'cast' => 'strval',
            ],
            'password_updated_at' => [
                'name' => 'passwordUpdatedAt',
                'cast' => static function ($timestamp) {
                    return $timestamp ? (int)$timestamp : null;
                },
            ],
            'is_enabled' => [
                'name' => 'enabled',
                'cast' => static function ($input): bool {
                    return $input == '1';
                },
            ],
            'max_login' => [
                'name' => 'maxLogin',
                'cast' => 'intval',
            ],
        ];
    }

    protected function convertColumnsToProperties(array $databaseValues): array
    {
        $propertyValues = [];
        $propertyMap = $this->getPropertyMap();

        foreach ($databaseValues as $columnName => $value) {
            $conversionFunction = $propertyMap[$columnName]['cast'];

            $propertyValues[$propertyMap[$columnName]['name']] = $conversionFunction($value);
        }

        return $propertyValues;
    }

    protected function fetchUserId(int $fieldId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                $this->connection->quoteIdentifier('contentobject_id')
            )
            ->from($this->connection->quoteIdentifier(ContentGateway::CONTENT_FIELD_TABLE))
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('id'),
                    ':fieldId'
                )
            )
            ->setParameter('fieldId', $fieldId, PDO::PARAM_INT)
        ;

        $statement = $query->executeQuery();

        return (int) $statement->fetchOne();
    }

    protected function fetchUserData(int $userId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                $this->connection->quoteIdentifier('usr.contentobject_id'),
                $this->connection->quoteIdentifier('usr.login'),
                $this->connection->quoteIdentifier('usr.email'),
                $this->connection->quoteIdentifier('usr.password_hash'),
                $this->connection->quoteIdentifier('usr.password_hash_type'),
                $this->connection->quoteIdentifier('usr.password_updated_at')
            )
            ->from($this->connection->quoteIdentifier(self::USER_TABLE), 'usr')
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('usr.contentobject_id'),
                    ':userId'
                )
            )
            ->setParameter('userId', $userId, PDO::PARAM_INT)
        ;

        $statement = $query->executeQuery();

        $rows = $statement->fetchAllAssociative();

        return isset($rows[0]) ? $this->convertColumnsToProperties($rows[0]) : [];
    }

    protected function fetchUserSettings(int $userId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                $this->connection->quoteIdentifier('s.is_enabled'),
                $this->connection->quoteIdentifier('s.max_login')
            )
            ->from($this->connection->quoteIdentifier(self::USER_SETTING_TABLE), 's')
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('s.user_id'),
                    ':userId'
                )
            )
            ->setParameter('userId', $userId, PDO::PARAM_INT)
        ;

        $statement = $query->executeQuery();

        $rows = $statement->fetchAllAssociative();

        return isset($rows[0]) ? $this->convertColumnsToProperties($rows[0]) : [];
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     */
    public function storeFieldData(VersionInfo $versionInfo, Field $field): bool
    {
        if ($field->value->externalData === null) {
            //to avoid unnecessary modifications when provided field is empty (like missing data for languageCode)
            return false;
        }

        try {
            if (!empty($this->fetchUserData($versionInfo->contentInfo->id))) {
                $this->updateFieldData($versionInfo, $field);
            } else {
                $this->insertFieldData($versionInfo, $field);
            }
        } catch (UniqueConstraintViolationException $e) {
            throw new ForbiddenException(
                'User "%login%" already exists',
                [
                    '%login%' => $field->value->externalData['login'] ?? '?',
                ]
            );
        }

        return true;
    }

    protected function insertFieldData(VersionInfo $versionInfo, Field $field): void
    {
        $insertQuery = $this->connection->createQueryBuilder();

        $insertQuery
            ->insert($this->connection->quoteIdentifier(self::USER_TABLE))
            ->setValue('contentobject_id', ':userId')
            ->setValue('login', ':login')
            ->setValue('email', ':email')
            ->setValue('password_hash', ':passwordHash')
            ->setValue('password_hash_type', ':passwordHashType')
            ->setValue('password_updated_at', ':passwordUpdatedAt')
            ->setParameter('userId', $versionInfo->contentInfo->id, ParameterType::INTEGER)
            ->setParameter('login', $field->value->externalData['login'], ParameterType::STRING)
            ->setParameter('email', $field->value->externalData['email'], ParameterType::STRING)
            ->setParameter('passwordHash', $field->value->externalData['passwordHash'], ParameterType::STRING)
            ->setParameter('passwordHashType', $field->value->externalData['passwordHashType'], ParameterType::INTEGER)
            ->setParameter('passwordUpdatedAt', $field->value->externalData['passwordUpdatedAt'])
        ;

        $insertQuery->executeStatement();

        $settingsQuery = $this->connection->createQueryBuilder();

        $settingsQuery
            ->insert($this->connection->quoteIdentifier(self::USER_SETTING_TABLE))
            ->setValue('user_id', ':userId')
            ->setValue('is_enabled', ':isEnabled')
            ->setValue('max_login', ':maxLogin')
            ->setParameter('userId', $versionInfo->contentInfo->id, ParameterType::INTEGER)
            ->setParameter('isEnabled', $field->value->externalData['enabled'], ParameterType::INTEGER)
            ->setParameter('maxLogin', $field->value->externalData['maxLogin'], ParameterType::INTEGER);

        $settingsQuery->executeStatement();
    }

    protected function updateFieldData(VersionInfo $versionInfo, Field $field): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->update($this->connection->quoteIdentifier(self::USER_TABLE))
            ->set('login', ':login')
            ->set('email', ':email')
            ->set('password_hash', ':passwordHash')
            ->set('password_hash_type', ':passwordHashType')
            ->set('password_updated_at', ':passwordUpdatedAt')
            ->setParameter('login', $field->value->externalData['login'], ParameterType::STRING)
            ->setParameter('email', $field->value->externalData['email'], ParameterType::STRING)
            ->setParameter('passwordHash', $field->value->externalData['passwordHash'], ParameterType::STRING)
            ->setParameter('passwordHashType', $field->value->externalData['passwordHashType'], ParameterType::INTEGER)
            ->setParameter('passwordUpdatedAt', $field->value->externalData['passwordUpdatedAt'])
            ->where(
                $queryBuilder->expr()->eq(
                    $this->connection->quoteIdentifier('contentobject_id'),
                    ':userId'
                )
            )
            ->setParameter('userId', $versionInfo->contentInfo->id, ParameterType::INTEGER)
        ;

        $queryBuilder->executeStatement();

        $settingsQuery = $this->connection->createQueryBuilder();

        $settingsQuery
            ->update($this->connection->quoteIdentifier(self::USER_SETTING_TABLE))
            ->set('is_enabled', ':isEnabled')
            ->set('max_login', ':maxLogin')
            ->setParameter('isEnabled', $field->value->externalData['enabled'], ParameterType::INTEGER)
            ->setParameter('maxLogin', $field->value->externalData['maxLogin'], ParameterType::INTEGER)
            ->where(
                $queryBuilder->expr()->eq(
                    $this->connection->quoteIdentifier('user_id'),
                    ':userId'
                )
            )
            ->setParameter('userId', $versionInfo->contentInfo->id, ParameterType::INTEGER);

        $settingsQuery->executeStatement();
    }

    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds): bool
    {
        // Delete external storage only, when when deleting last relation to fieldType
        // to avoid removing it when deleting draft, translation or by exceeding archive limit
        if (!$this->isLastRelationToFieldType($fieldIds)) {
            return false;
        }

        $query = $this->connection->createQueryBuilder();
        $query
            ->delete($this->connection->quoteIdentifier(self::USER_SETTING_TABLE))
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('user_id'),
                    ':userId'
                )
            )
            ->setParameter('userId', $versionInfo->contentInfo->id, ParameterType::INTEGER);

        $query->executeStatement();

        $query = $this->connection->createQueryBuilder();
        $query
            ->delete($this->connection->quoteIdentifier(self::USER_TABLE))
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('contentobject_id'),
                    ':userId'
                )
            )
            ->setParameter('userId', $versionInfo->contentInfo->id, ParameterType::INTEGER);

        $query->executeStatement();

        return true;
    }

    /**
     * @param int[] $fieldIds
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function isLastRelationToFieldType(array $fieldIds): bool
    {
        $countExpr = 'COUNT(id)';
        $checkQuery = $this->connection->createQueryBuilder();
        $checkQuery
            ->select($countExpr)
            ->from(ContentGateway::CONTENT_FIELD_TABLE)
            ->where(
                $checkQuery->expr()->in(
                    $this->connection->quoteIdentifier('id'),
                    ':fieldIds'
                )
            )
            ->setParameter('fieldIds', $fieldIds, Connection::PARAM_INT_ARRAY)
            ->groupBy('id')
            ->having($countExpr . ' > 1');

        $numRows = (int)$checkQuery->executeQuery()->fetchOne();

        return $numRows === 0;
    }

    public function countUsersWithUnsupportedHashType(array $supportedHashTypes): int
    {
        $selectQuery = $this->connection->createQueryBuilder();

        $selectQuery
            ->select('COUNT(u.login)')
            ->from(self::USER_TABLE, 'u')
            ->andWhere(
                $selectQuery->expr()->notIn('u.password_hash_type', ':supportedPasswordHashes')
            )
            ->setParameter('supportedPasswordHashes', $supportedHashTypes, Connection::PARAM_INT_ARRAY);

        return $selectQuery
            ->executeQuery()
            ->fetchOne();
    }
}
