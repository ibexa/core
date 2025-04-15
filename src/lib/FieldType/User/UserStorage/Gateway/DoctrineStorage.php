<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\User\UserStorage\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\Base\Exceptions\ForbiddenException;
use Ibexa\Core\FieldType\User\UserStorage\Gateway;

/**
 * User DoctrineStorage gateway.
 */
class DoctrineStorage extends Gateway
{
    public const string USER_TABLE = 'ezuser';
    public const string USER_SETTING_TABLE = 'ezuser_setting';
    private const string USER_ID_PARAM_NAME = 'userId';
    private const string LOGIN_PARAM_NAME = 'login';
    private const string EMAIL_PARAM_NAME = 'email';
    private const string PASSWORD_HASH_PARAM_NAME = 'passwordHash';
    private const string PASSWORD_HASH_TYPE_PARAM_NAME = 'passwordHashType';
    private const string PASSWORD_UPDATED_AT_PARAM_NAME = 'passwordUpdatedAt';
    private const string IS_ENABLED_PARAM_NAME = 'isEnabled';
    private const string MAX_LOGIN_PARAM_NAME = 'maxLogin';

    protected Connection $connection;

    /**
     * Default values for user fields.
     *
     * @var array{
     *     hasStoredLogin: bool,
     *     contentId: int|null,
     *     login: string|null,
     *     email: string|null,
     *     passwordHash: string|null,
     *     passwordHashType: string|null,
     *     passwordUpdatedAt: int|null,
     *     enabled: bool,
     *     maxLogin: int|null
     * }
     */
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
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getFieldData(int $fieldId, ?int $userId = null): array
    {
        $userId = $userId ?: $this->fetchUserId($fieldId);
        $userData = $this->fetchUserData($userId);

        if (!isset($userData['login'])) {
            return $this->defaultValues;
        }

        return array_merge(
            $this->defaultValues,
            [
                'hasStoredLogin' => true,
            ],
            $userData,
            $this->fetchUserSettings($userId)
        );
    }

    /**
     * Map legacy database column names to property names.
     *
     * @return array<string, array{name: string, cast: callable}>
     */
    protected function getPropertyMap(): array
    {
        return [
            'has_stored_login' => [
                'name' => 'hasStoredlogin',
                'cast' => static fn ($input): bool => ((string)$input) === '1',
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
                'cast' => static fn ($timestamp): ?int => $timestamp ? (int)$timestamp : null,
            ],
            'is_enabled' => [
                'name' => 'enabled',
                'cast' => static fn ($input): bool => ((string)$input) === '1',
            ],
            'max_login' => [
                'name' => 'maxLogin',
                'cast' => 'intval',
            ],
        ];
    }

    /**
     * Convert the given database values to properties.
     *
     * @phpstan-param array<string, scalar> $databaseValues
     *
     * @return array{
     *      contentId?: int|null,
     *      login?: string|null,
     *      email?: string|null,
     *      passwordHash?: string|null,
     *      passwordHashType?: string|null,
     *      passwordUpdatedAt?: int|null
     *  }
     */
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

    /**
     * Fetch user content object id for the given field id.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function fetchUserId(int $fieldId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                $this->connection->quoteIdentifier('contentobject_id')
            )
            ->from($this->connection->quoteIdentifier('ezcontentobject_attribute'))
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('id'),
                    ':fieldId'
                )
            )
            ->setParameter('fieldId', $fieldId, ParameterType::INTEGER)
        ;

        $statement = $query->executeQuery();

        return (int) $statement->fetchOne();
    }

    /**
     * Fetch user data.
     *
     * @return array{
     *     contentId: int|null,
     *     login: string|null,
     *     email: string|null,
     *     passwordHash: string|null,
     *     passwordHashType: string|null,
     *     passwordUpdatedAt: int|null
     * }
     *
     * @throws \Doctrine\DBAL\Exception
     */
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
                    ':' . self::USER_ID_PARAM_NAME
                )
            )
            ->setParameter(self::USER_ID_PARAM_NAME, $userId, ParameterType::INTEGER)
        ;

        $row = $query->executeQuery()->fetchAssociative();

        return false !== $row ? $this->convertColumnsToProperties($row) : [];
    }

    /**
     * Fetch user settings.
     *
     * @param int $userId
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\Exception
     */
    /**
     * Fetch user settings.
     *
     * @return array{
     *     enabled: bool,
     *     maxLogin: int|null
     * }
     *
     * @throws \Doctrine\DBAL\Exception
     */
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
                    ':' . self::USER_ID_PARAM_NAME
                )
            )
            ->setParameter(self::USER_ID_PARAM_NAME, $userId, ParameterType::INTEGER)
        ;

        $row = $query->executeQuery()->fetchAssociative();

        return false !== $row ? $this->convertColumnsToProperties($row) : [];
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Doctrine\DBAL\Exception
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
        } catch (UniqueConstraintViolationException) {
            throw new ForbiddenException(
                'User "%login%" already exists',
                [
                    '%login%' => $field->value->externalData['login'] ?? '?',
                ]
            );
        }

        return true;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function insertFieldData(VersionInfo $versionInfo, Field $field): void
    {
        $insertQuery = $this->connection->createQueryBuilder();

        $insertQuery
            ->insert($this->connection->quoteIdentifier(self::USER_TABLE))
            ->setValue('contentobject_id', ':' . self::USER_ID_PARAM_NAME)
            ->setValue('login', ':' . self::LOGIN_PARAM_NAME)
            ->setValue('email', ':' . self::EMAIL_PARAM_NAME)
            ->setValue('password_hash', ':' . self::PASSWORD_HASH_PARAM_NAME)
            ->setValue('password_hash_type', ':' . self::PASSWORD_HASH_TYPE_PARAM_NAME)
            ->setValue('password_updated_at', ':' . self::PASSWORD_UPDATED_AT_PARAM_NAME)
            ->setParameter(self::USER_ID_PARAM_NAME, $versionInfo->contentInfo->id, ParameterType::INTEGER)
            ->setParameter(self::LOGIN_PARAM_NAME, $field->value->externalData['login'])
            ->setParameter(self::EMAIL_PARAM_NAME, $field->value->externalData['email'])
            ->setParameter(self::PASSWORD_HASH_PARAM_NAME, $field->value->externalData['passwordHash'])
            ->setParameter(self::PASSWORD_HASH_TYPE_PARAM_NAME, $field->value->externalData['passwordHashType'], ParameterType::INTEGER)
            ->setParameter(self::PASSWORD_UPDATED_AT_PARAM_NAME, $field->value->externalData['passwordUpdatedAt'])
        ;

        $insertQuery->executeStatement();

        $settingsQuery = $this->connection->createQueryBuilder();

        $settingsQuery
            ->insert($this->connection->quoteIdentifier(self::USER_SETTING_TABLE))
            ->setValue('user_id', ':' . self::USER_ID_PARAM_NAME)
            ->setValue('is_enabled', ':' . self::IS_ENABLED_PARAM_NAME)
            ->setValue('max_login', ':' . self::MAX_LOGIN_PARAM_NAME)
            ->setParameter(self::USER_ID_PARAM_NAME, $versionInfo->contentInfo->id, ParameterType::INTEGER)
            ->setParameter(self::IS_ENABLED_PARAM_NAME, $field->value->externalData['enabled'], ParameterType::INTEGER)
            ->setParameter(self::MAX_LOGIN_PARAM_NAME, $field->value->externalData['maxLogin'], ParameterType::INTEGER);

        $settingsQuery->executeQuery();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function updateFieldData(VersionInfo $versionInfo, Field $field): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->update($this->connection->quoteIdentifier(self::USER_TABLE))
            ->set('login', ':' . self::LOGIN_PARAM_NAME)
            ->set('email', ':' . self::EMAIL_PARAM_NAME)
            ->set('password_hash', ':' . self::PASSWORD_HASH_PARAM_NAME)
            ->set('password_hash_type', ':' . self::PASSWORD_HASH_TYPE_PARAM_NAME)
            ->set('password_updated_at', ':' . self::PASSWORD_UPDATED_AT_PARAM_NAME)
            ->setParameter(self::LOGIN_PARAM_NAME, $field->value->externalData['login'])
            ->setParameter(self::EMAIL_PARAM_NAME, $field->value->externalData['email'])
            ->setParameter(self::PASSWORD_HASH_PARAM_NAME, $field->value->externalData['passwordHash'])
            ->setParameter(self::PASSWORD_HASH_TYPE_PARAM_NAME, $field->value->externalData['passwordHashType'], ParameterType::INTEGER)
            ->setParameter(self::PASSWORD_UPDATED_AT_PARAM_NAME, $field->value->externalData['passwordUpdatedAt'])
            ->where(
                $queryBuilder->expr()->eq(
                    $this->connection->quoteIdentifier('contentobject_id'),
                    ':' . self::USER_ID_PARAM_NAME
                )
            )
            ->setParameter(self::USER_ID_PARAM_NAME, $versionInfo->contentInfo->id, ParameterType::INTEGER)
        ;

        $queryBuilder->executeStatement();

        $settingsQuery = $this->connection->createQueryBuilder();

        $settingsQuery
            ->update($this->connection->quoteIdentifier(self::USER_SETTING_TABLE))
            ->set('is_enabled', ':' . self::IS_ENABLED_PARAM_NAME)
            ->set('max_login', ':' . self::MAX_LOGIN_PARAM_NAME)
            ->setParameter(self::IS_ENABLED_PARAM_NAME, $field->value->externalData['enabled'], ParameterType::INTEGER)
            ->setParameter(self::MAX_LOGIN_PARAM_NAME, $field->value->externalData['maxLogin'], ParameterType::INTEGER)
            ->where(
                $queryBuilder->expr()->eq(
                    $this->connection->quoteIdentifier('user_id'),
                    ':' . self::USER_ID_PARAM_NAME
                )
            )
            ->setParameter(self::USER_ID_PARAM_NAME, $versionInfo->contentInfo->id, ParameterType::INTEGER);

        $settingsQuery->executeStatement();
    }

    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds): bool
    {
        // Delete external storage only, when deleting last relation to fieldType
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
                    ':' . self::USER_ID_PARAM_NAME
                )
            )
            ->setParameter(self::USER_ID_PARAM_NAME, $versionInfo->contentInfo->id, ParameterType::INTEGER);

        $query->executeStatement();

        $query = $this->connection->createQueryBuilder();
        $query
            ->delete($this->connection->quoteIdentifier(self::USER_TABLE))
            ->where(
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('contentobject_id'),
                    ':' . self::USER_ID_PARAM_NAME
                )
            )
            ->setParameter(self::USER_ID_PARAM_NAME, $versionInfo->contentInfo->id, ParameterType::INTEGER);

        $query->executeStatement();

        return true;
    }

    /**
     * @param int[] $fieldIds
     *
     * @return bool
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function isLastRelationToFieldType(array $fieldIds): bool
    {
        $countExpr = 'COUNT(id)';

        $checkQuery = $this->connection->createQueryBuilder();
        $checkQuery
            ->select($countExpr)
            ->from('ezcontentobject_attribute')
            ->where(
                $checkQuery->expr()->in(
                    $this->connection->quoteIdentifier('id'),
                    ':fieldIds'
                )
            )
            ->setParameter('fieldIds', $fieldIds, ArrayParameterType::INTEGER)
            ->groupBy('id')
            ->having($countExpr . ' > 1');

        $numRows = (int)$checkQuery->executeQuery()->fetchOne();

        return $numRows === 0;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function countUsersWithUnsupportedHashType(array $supportedHashTypes): int
    {
        $selectQuery = $this->connection->createQueryBuilder();

        $selectQuery
            ->select(
                'COUNT(u.login)'
            )
            ->from(self::USER_TABLE, 'u')
            ->andWhere(
                $selectQuery->expr()->notIn('u.password_hash_type', ':supportedPasswordHashes')
            )
            ->setParameter('supportedPasswordHashes', $supportedHashTypes, ArrayParameterType::INTEGER);

        return $selectQuery->executeQuery()->fetchOne();
    }
}
