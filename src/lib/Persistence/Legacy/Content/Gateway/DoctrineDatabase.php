<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use DOMDocument;
use DOMXPath;
use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct as RelationCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\UpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Base\Exceptions\NotFoundException as NotFound;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Gateway\DoctrineDatabase\QueryBuilder;
use Ibexa\Core\Persistence\Legacy\Content\Language\MaskGenerator;
use Ibexa\Core\Persistence\Legacy\Content\Language\MaskGenerator as LanguageMaskGenerator;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use Ibexa\Core\Persistence\Legacy\SharedGateway\Gateway as SharedGateway;
use LogicException;

/**
 * Doctrine-database-based content gateway.
 *
 * @internal Gateway implementation is considered internal. Use Persistence Content Handler instead.
 *
 * @see \Ibexa\Contracts\Core\Persistence\Content\Handler
 */
final class DoctrineDatabase extends Gateway
{
    /**
     * Pre-computed integer constant which, when combined with proper bit-wise operator,
     * removes an always available flag from the mask.
     */
    private const int REMOVE_ALWAYS_AVAILABLE_LANG_MASK_OPERAND = -2;
    private const string CONTENT_ID_PARAM_NAME = 'content_id';
    private const string LANGUAGE_MASK_PARAM_NAME = 'languageMask';
    private const string CONTENT_ITEM_ID_EQ_CONTENT_ID_PARAM_COMPARISON = 'contentobject_id = :content_id';
    private const string VERSION_NO_EQ_VERSION_NO_PARAM_COMPARISON = 'version = :version_no';
    private const string VERSION_NO_PARAM_NAME = 'version_no';
    private const string LANGUAGE_MASK_OPERAND_PARAM_NAME = 'languageMaskOperand';
    private const string STATUS_PARAM_NAME = 'status';
    private const string FIELD_DEFINITION_ID_PARAM_NAME = 'field_definition_id';
    private const string DATA_TYPE_STRING_PARAM_NAME = 'data_type_string';
    private const string LANGUAGE_CODE_PARAM_NAME = 'language_code';
    private const string DATA_FLOAT_PARAM_NAME = 'data_float';
    private const string DATA_INT_PARAM_NAME = 'data_int';
    private const string DATA_TEXT_PARAM_NAME = 'data_text';
    private const string SORT_KEY_INT_PARAM_NAME = 'sort_key_int';
    private const string SORT_KEY_STRING_PARAM_NAME = 'sort_key_string';
    private const string LANGUAGE_ID_PARAM_NAME = 'language_id';
    private const string USER_ID_PARAM_NAME = 'user_id';
    private const string RELATION_TYPE_PARAM_NAME = 'relation_type';
    private const string CONTENT_VERSION_VERSION_NO_PARAM_NAME = 'content_version = :version_no';
    private const string ID_RELATION_ID_PARAM_COMPARISON = 'id = :relation_id';
    private const string RELATION_ID_PARAM_NAME = 'relation_id';

    /**
     * The native Doctrine connection.
     *
     * Meant to be used to transition from eZ/Zeta interface to Doctrine.
     */
    protected Connection $connection;

    /**
     * Query builder.
     */
    protected QueryBuilder $queryBuilder;

    protected Handler $languageHandler;

    protected MaskGenerator $languageMaskGenerator;

    private SharedGateway $sharedGateway;

    private AbstractPlatform $databasePlatform;

    public function __construct(
        Connection $connection,
        SharedGateway $sharedGateway,
        QueryBuilder $queryBuilder,
        LanguageHandler $languageHandler,
        LanguageMaskGenerator $languageMaskGenerator
    ) {
        $this->connection = $connection;
        $this->sharedGateway = $sharedGateway;
        $this->queryBuilder = $queryBuilder;
        $this->languageHandler = $languageHandler;
        $this->languageMaskGenerator = $languageMaskGenerator;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function insertContentObject(CreateStruct $struct, int $currentVersionNo = 1): int
    {
        $initialLanguageId = !empty($struct->mainLanguageId) ? $struct->mainLanguageId : $struct->initialLanguageId;
        $initialLanguageCode = $this->languageHandler->load($initialLanguageId)->languageCode;

        $name = $struct->name[$initialLanguageCode] ?? '';

        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::CONTENT_ITEM_TABLE)
            ->values(
                [
                    'current_version' => $query->createPositionalParameter(
                        $currentVersionNo,
                        ParameterType::INTEGER
                    ),
                    'name' => $query->createPositionalParameter($name),
                    'contentclass_id' => $query->createPositionalParameter(
                        $struct->typeId,
                        ParameterType::INTEGER
                    ),
                    'section_id' => $query->createPositionalParameter(
                        $struct->sectionId,
                        ParameterType::INTEGER
                    ),
                    'owner_id' => $query->createPositionalParameter(
                        $struct->ownerId,
                        ParameterType::INTEGER
                    ),
                    'initial_language_id' => $query->createPositionalParameter(
                        $initialLanguageId,
                        ParameterType::INTEGER
                    ),
                    'remote_id' => $query->createPositionalParameter($struct->remoteId),
                    'modified' => $query->createPositionalParameter(0, ParameterType::INTEGER),
                    'published' => $query->createPositionalParameter(0, ParameterType::INTEGER),
                    'status' => $query->createPositionalParameter(
                        ContentInfo::STATUS_DRAFT,
                        ParameterType::INTEGER
                    ),
                    'language_mask' => $query->createPositionalParameter(
                        $this->languageMaskGenerator->generateLanguageMaskForFields(
                            $struct->fields,
                            $initialLanguageCode,
                            $struct->alwaysAvailable
                        ),
                        ParameterType::INTEGER
                    ),
                ]
            );

        $query->executeStatement();

        return (int)$this->connection->lastInsertId(self::CONTENT_ITEM_SEQ);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function insertVersion(VersionInfo $versionInfo, array $fields): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::CONTENT_VERSION_TABLE)
            ->values(
                [
                    'version' => $query->createPositionalParameter(
                        $versionInfo->versionNo,
                        ParameterType::INTEGER
                    ),
                    'modified' => $query->createPositionalParameter(
                        $versionInfo->modificationDate,
                        ParameterType::INTEGER
                    ),
                    'creator_id' => $query->createPositionalParameter(
                        $versionInfo->creatorId,
                        ParameterType::INTEGER
                    ),
                    'created' => $query->createPositionalParameter(
                        $versionInfo->creationDate,
                        ParameterType::INTEGER
                    ),
                    'status' => $query->createPositionalParameter(
                        $versionInfo->status,
                        ParameterType::INTEGER
                    ),
                    'initial_language_id' => $query->createPositionalParameter(
                        $this->languageHandler->loadByLanguageCode(
                            $versionInfo->initialLanguageCode
                        )->id,
                        ParameterType::INTEGER
                    ),
                    'contentobject_id' => $query->createPositionalParameter(
                        $versionInfo->contentInfo->id,
                        ParameterType::INTEGER
                    ),
                    'language_mask' => $query->createPositionalParameter(
                        $this->languageMaskGenerator->generateLanguageMaskForFields(
                            $fields,
                            $versionInfo->initialLanguageCode,
                            $versionInfo->contentInfo->alwaysAvailable
                        ),
                        ParameterType::INTEGER
                    ),
                ]
            );

        $query->executeStatement();

        return (int)$this->connection->lastInsertId(self::CONTENT_VERSION_SEQ);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateContent(
        int $contentId,
        MetadataUpdateStruct $struct,
        ?VersionInfo $prePublishVersionInfo = null
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query->update(self::CONTENT_ITEM_TABLE);

        $fieldsForUpdateMap = [
            'name' => [
                'value' => $struct->name,
                'type' => ParameterType::STRING,
            ],
            'initial_language_id' => [
                'value' => $struct->mainLanguageId,
                'type' => ParameterType::INTEGER,
            ],
            'modified' => [
                'value' => $struct->modificationDate,
                'type' => ParameterType::INTEGER,
            ],
            'owner_id' => [
                'value' => $struct->ownerId,
                'type' => ParameterType::INTEGER,
            ],
            'published' => [
                'value' => $struct->publicationDate,
                'type' => ParameterType::INTEGER,
            ],
            'remote_id' => [
                'value' => $struct->remoteId,
                'type' => ParameterType::STRING,
            ],
            'is_hidden' => [
                'value' => $struct->isHidden,
                'type' => ParameterType::BOOLEAN,
            ],
        ];

        $updated = false;
        foreach ($fieldsForUpdateMap as $fieldName => $field) {
            if (null === $field['value']) {
                continue;
            }
            $query->set(
                $fieldName,
                $query->createNamedParameter($field['value'], $field['type'], ":{$fieldName}")
            );
            $updated = true;
        }

        if ($prePublishVersionInfo !== null) {
            $mask = $this->languageMaskGenerator->generateLanguageMaskFromLanguageCodes(
                $prePublishVersionInfo->languageCodes,
                $struct->alwaysAvailable ?? $prePublishVersionInfo->contentInfo->alwaysAvailable
            );
            $query->set(
                'language_mask',
                $query->createNamedParameter($mask, ParameterType::INTEGER, ':' . self::LANGUAGE_MASK_PARAM_NAME)
            );
            $updated = true;
        }

        $query->where(
            $query->expr()->eq(
                'id',
                $query->createNamedParameter($contentId, ParameterType::INTEGER, ':' . self::CONTENT_ID_PARAM_NAME)
            )
        );

        if ($updated) {
            $query->executeStatement();
        }

        // Handle alwaysAvailable flag update separately as it's a more complex task and has impact on several tables
        if (isset($struct->alwaysAvailable) || isset($struct->mainLanguageId)) {
            $this->updateAlwaysAvailableFlag($contentId, $struct->alwaysAvailable);
        }
    }

    /**
     * Updates version $versionNo for content identified by $contentId, in respect to $struct.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateVersion(int $contentId, int $versionNo, UpdateStruct $struct): void
    {
        $query = $this->connection->createQueryBuilder();

        $query
            ->update(self::CONTENT_VERSION_TABLE)
            ->set('creator_id', ':creator_id')
            ->set('modified', ':modified')
            ->set('initial_language_id', ':initial_language_id')
            ->set(
                'language_mask',
                $this->getDatabasePlatform()->getBitOrComparisonExpression(
                    'language_mask',
                    ':language_mask'
                )
            )
            ->setParameter('creator_id', $struct->creatorId, ParameterType::INTEGER)
            ->setParameter('modified', $struct->modificationDate, ParameterType::INTEGER)
            ->setParameter(
                'initial_language_id',
                $struct->initialLanguageId,
                ParameterType::INTEGER
            )
            ->setParameter(
                'language_mask',
                $this->languageMaskGenerator->generateLanguageMaskForFields(
                    $struct->fields,
                    $this->languageHandler->load($struct->initialLanguageId)->languageCode,
                    false
                ),
                ParameterType::INTEGER
            )
            ->where(self::CONTENT_ITEM_ID_EQ_CONTENT_ID_PARAM_COMPARISON)
            ->andWhere(self::VERSION_NO_EQ_VERSION_NO_PARAM_COMPARISON)
            ->setParameter('content_id', $contentId, ParameterType::INTEGER)
            ->setParameter('version_no', $versionNo, ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateAlwaysAvailableFlag(int $contentId, ?bool $newAlwaysAvailable = null): void
    {
        // We will need to know some info on the current language mask to update the flag
        // everywhere needed
        $contentInfoRow = $this->loadContentInfo($contentId);
        $versionNo = (int)$contentInfoRow['current_version'];
        $languageMask = (int)$contentInfoRow['language_mask'];
        $initialLanguageId = (int)$contentInfoRow['initial_language_id'];
        if (null === $newAlwaysAvailable) {
            $newAlwaysAvailable = 1 === ($languageMask & 1);
        }

        $this->updateContentItemAlwaysAvailableFlag($contentId, $newAlwaysAvailable);
        $this->updateContentNameAlwaysAvailableFlag(
            $contentId,
            $versionNo,
            $newAlwaysAvailable
        );
        $this->updateContentFieldsAlwaysAvailableFlag(
            $contentId,
            $versionNo,
            $newAlwaysAvailable,
            $languageMask,
            $initialLanguageId
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function updateContentItemAlwaysAvailableFlag(
        int $contentId,
        bool $alwaysAvailable
    ): void {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->update(self::CONTENT_ITEM_TABLE);
        $this
            ->setLanguageMaskForUpdateQuery($alwaysAvailable, $query, 'language_mask')
            ->where(
                $expr->eq(
                    'id',
                    $query->createNamedParameter($contentId, ParameterType::INTEGER, ':' . self::CONTENT_ID_PARAM_NAME)
                )
            );
        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function updateContentNameAlwaysAvailableFlag(
        int $contentId,
        int $versionNo,
        bool $alwaysAvailable
    ): void {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->update(self::CONTENT_NAME_TABLE);
        $this
            ->setLanguageMaskForUpdateQuery($alwaysAvailable, $query, 'language_id')
            ->where(
                $expr->eq(
                    'contentobject_id',
                    $query->createNamedParameter($contentId, ParameterType::INTEGER, ':' . self::CONTENT_ID_PARAM_NAME)
                )
            )
            ->andWhere(
                $expr->eq(
                    'content_version',
                    $query->createNamedParameter($versionNo, ParameterType::INTEGER, ':' . self::VERSION_NO_PARAM_NAME)
                )
            );
        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function updateContentFieldsAlwaysAvailableFlag(
        int $contentId,
        int $versionNo,
        bool $alwaysAvailable,
        int $languageMask,
        int $initialLanguageId
    ): void {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->update(self::CONTENT_FIELD_TABLE)
            ->where(
                $expr->eq(
                    'contentobject_id',
                    $query->createNamedParameter($contentId, ParameterType::INTEGER, ':' . self::CONTENT_ID_PARAM_NAME)
                )
            )
            ->andWhere(
                $expr->eq(
                    'version',
                    $query->createNamedParameter($versionNo, ParameterType::INTEGER, ':' . self::VERSION_NO_PARAM_NAME)
                )
            );

        // If there is only a single language, update all fields and return
        if (!$this->languageMaskGenerator->isLanguageMaskComposite($languageMask)) {
            $this->setLanguageMaskForUpdateQuery($alwaysAvailable, $query, 'language_id');

            $query->executeStatement();

            return;
        }

        // Otherwise:
        // 1. Remove always available flag on all fields
        $query
            ->set(
                'language_id',
                $this->getDatabasePlatform()->getBitAndComparisonExpression(
                    'language_id',
                    ':' . self::LANGUAGE_MASK_OPERAND_PARAM_NAME
                )
            )
            ->setParameter(self::LANGUAGE_MASK_OPERAND_PARAM_NAME, self::REMOVE_ALWAYS_AVAILABLE_LANG_MASK_OPERAND)
        ;
        $query->executeStatement();

        // 2. If Content is always available set the flag only on fields in main language
        if ($alwaysAvailable) {
            $query
                ->set(
                    'language_id',
                    $this->getDatabasePlatform()->getBitOrComparisonExpression(
                        'language_id',
                        ':' . self::LANGUAGE_MASK_OPERAND_PARAM_NAME
                    )
                )
                ->setParameter(self::LANGUAGE_MASK_OPERAND_PARAM_NAME, 1);

            $query->andWhere(
                $expr->gt(
                    $this->getDatabasePlatform()->getBitAndComparisonExpression(
                        'language_id',
                        $query->createNamedParameter($initialLanguageId, ParameterType::INTEGER, ':initialLanguageId')
                    ),
                    $query->createNamedParameter(0, ParameterType::INTEGER, ':zero')
                )
            );
            $query->executeStatement();
        }
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Doctrine\DBAL\Exception
     */
    public function setStatus(int $contentId, int $version, int $status): bool
    {
        if ($status !== APIVersionInfo::STATUS_PUBLISHED) {
            $query = $this->queryBuilder->getSetVersionStatusQuery($contentId, $version, $status);
            $rowCount = $query->executeStatement();

            return $rowCount > 0;
        }

        // If the version's status is PUBLISHED, we use dedicated method for publishing
        $this->setPublishedStatus($contentId, $version);

        return true;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Doctrine\DBAL\Exception
     */
    public function setPublishedStatus(int $contentId, int $versionNo): void
    {
        $query = $this->queryBuilder->getSetVersionStatusQuery(
            $contentId,
            $versionNo,
            VersionInfo::STATUS_PUBLISHED
        );

        /* this part allows set status `published` only if there is no other published version of the content */
        $notExistPublishedVersion = <<<SQL
            NOT EXISTS (
                SELECT 1 FROM (
                    SELECT 1 FROM ezcontentobject_version
                    WHERE contentobject_id = :contentId AND status = :status
                ) as V
            )
            SQL;

        $query->andWhere($notExistPublishedVersion);
        if (0 === $query->executeStatement()) {
            throw new BadStateException(
                '$contentId',
                "Someone just published another version of Content item {$contentId}"
            );
        }
        $this->markContentAsPublished($contentId, $versionNo);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function markContentAsPublished(int $contentId, int $versionNo): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update('ezcontentobject')
            ->set('status', ':' . self::STATUS_PARAM_NAME)
            ->set('current_version', ':' . self::VERSION_NO_PARAM_NAME)
            ->where($query->expr()->eq('id', ':' . self::CONTENT_ID_PARAM_NAME))
            ->setParameter(self::STATUS_PARAM_NAME, ContentInfo::STATUS_PUBLISHED, ParameterType::INTEGER)
            ->setParameter(self::VERSION_NO_PARAM_NAME, $versionNo, ParameterType::INTEGER)
            ->setParameter(self::CONTENT_ID_PARAM_NAME, $contentId, ParameterType::INTEGER);
        $query->executeStatement();
    }

    /**
     * @return int ID
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function insertNewField(Content $content, Field $field, StorageFieldValue $value): int
    {
        $query = $this->connection->createQueryBuilder();

        $this->setInsertFieldValues($query, $content, $field, $value);

        // Insert with auto increment ID
        $nextId = $this->sharedGateway->getColumnNextIntegerValue(
            self::CONTENT_FIELD_TABLE,
            'id',
            self::CONTENT_FIELD_SEQ
        );
        // avoid trying to insert NULL to trigger default column value behavior
        if (null !== $nextId) {
            $query
                ->setValue('id', ':field_id')
                ->setParameter('field_id', $nextId, ParameterType::INTEGER);
        }

        $query->executeStatement();

        return $this->sharedGateway->getLastInsertedId(self::CONTENT_FIELD_SEQ);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function insertExistingField(
        Content $content,
        Field $field,
        StorageFieldValue $value
    ): void {
        $query = $this->connection->createQueryBuilder();

        $this->setInsertFieldValues($query, $content, $field, $value);

        $query
            ->setValue('id', ':field_id')
            ->setParameter('field_id', $field->id, ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * Set the given query field (ezcontentobject_attribute) values.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function setInsertFieldValues(
        DoctrineQueryBuilder $query,
        Content $content,
        Field $field,
        StorageFieldValue $value
    ): void {
        $query
            ->insert(self::CONTENT_FIELD_TABLE)
            ->values(
                [
                    'contentobject_id' => ':' . self::CONTENT_ID_PARAM_NAME,
                    'contentclassattribute_id' => ':' . self::FIELD_DEFINITION_ID_PARAM_NAME,
                    'data_type_string' => ':' . self::DATA_TYPE_STRING_PARAM_NAME,
                    'language_code' => ':' . self::LANGUAGE_CODE_PARAM_NAME,
                    'version' => ':' . self::VERSION_NO_PARAM_NAME,
                    'data_float' => ':' . self::DATA_FLOAT_PARAM_NAME,
                    'data_int' => ':' . self::DATA_INT_PARAM_NAME,
                    'data_text' => ':' . self::DATA_TEXT_PARAM_NAME,
                    'sort_key_int' => ':' . self::SORT_KEY_INT_PARAM_NAME,
                    'sort_key_string' => ':' . self::SORT_KEY_STRING_PARAM_NAME,
                    'language_id' => ':' . self::LANGUAGE_ID_PARAM_NAME,
                ]
            )
            ->setParameter(
                self::CONTENT_ID_PARAM_NAME,
                $content->versionInfo->contentInfo->id,
                ParameterType::INTEGER
            )
            ->setParameter(self::FIELD_DEFINITION_ID_PARAM_NAME, $field->fieldDefinitionId, ParameterType::INTEGER)
            ->setParameter(self::DATA_TYPE_STRING_PARAM_NAME, $field->type)
            ->setParameter(self::LANGUAGE_CODE_PARAM_NAME, $field->languageCode)
            ->setParameter(self::VERSION_NO_PARAM_NAME, $field->versionNo, ParameterType::INTEGER)
            ->setParameter(self::DATA_FLOAT_PARAM_NAME, $value->dataFloat)
            ->setParameter(self::DATA_INT_PARAM_NAME, $value->dataInt, ParameterType::INTEGER)
            ->setParameter(self::DATA_TEXT_PARAM_NAME, $value->dataText)
            ->setParameter(self::SORT_KEY_INT_PARAM_NAME, $value->sortKeyInt, ParameterType::INTEGER)
            ->setParameter(
                'sort_key_string',
                mb_substr($value->sortKeyString, 0, 255)
            )
            ->setParameter(
                'language_id',
                $this->languageMaskGenerator->generateLanguageIndicator(
                    $field->languageCode,
                    $this->isLanguageAlwaysAvailable($content, $field->languageCode)
                ),
                ParameterType::INTEGER
            );
    }

    /**
     * Check if $languageCode is always available in $content.
     */
    private function isLanguageAlwaysAvailable(Content $content, string $languageCode): bool
    {
        return
            $content->versionInfo->contentInfo->alwaysAvailable &&
            $content->versionInfo->contentInfo->mainLanguageCode === $languageCode
        ;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateField(Field $field, StorageFieldValue $value): void
    {
        // Note, no need to care for language_id here, since Content->$alwaysAvailable
        // cannot change on update
        $query = $this->connection->createQueryBuilder();
        $this->setFieldUpdateValues($query, $value);
        $query
            ->where('id = :field_id')
            ->andWhere(self::VERSION_NO_EQ_VERSION_NO_PARAM_COMPARISON)
            ->setParameter('field_id', $field->id, ParameterType::INTEGER)
            ->setParameter('version_no', $field->versionNo, ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * Set update fields on $query based on $value.
     */
    private function setFieldUpdateValues(
        DoctrineQueryBuilder $query,
        StorageFieldValue $value
    ): void {
        $query
            ->update(self::CONTENT_FIELD_TABLE)
            ->set('data_float', ':' . self::DATA_FLOAT_PARAM_NAME)
            ->set('data_int', ':' . self::DATA_INT_PARAM_NAME)
            ->set('data_text', ':' . self::DATA_TEXT_PARAM_NAME)
            ->set('sort_key_int', ':' . self::SORT_KEY_INT_PARAM_NAME)
            ->set('sort_key_string', ':' . self::SORT_KEY_STRING_PARAM_NAME)
            ->setParameter('data_float', $value->dataFloat)
            ->setParameter('data_int', $value->dataInt, ParameterType::INTEGER)
            ->setParameter('data_text', $value->dataText)
            ->setParameter('sort_key_int', $value->sortKeyInt, ParameterType::INTEGER)
            ->setParameter('sort_key_string', mb_substr($value->sortKeyString, 0, 255))
        ;
    }

    /**
     * Update an existing, non-translatable field.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateNonTranslatableField(
        Field $field,
        StorageFieldValue $value,
        int $contentId
    ): void {
        // Note, no need to care for language_id here, since Content->$alwaysAvailable
        // cannot change on update
        $query = $this->connection->createQueryBuilder();
        $this->setFieldUpdateValues($query, $value);
        $query
            ->where('contentclassattribute_id = :field_definition_id')
            ->andWhere(self::CONTENT_ITEM_ID_EQ_CONTENT_ID_PARAM_COMPARISON)
            ->andWhere(self::VERSION_NO_EQ_VERSION_NO_PARAM_COMPARISON)
            ->setParameter('field_definition_id', $field->fieldDefinitionId, ParameterType::INTEGER)
            ->setParameter('content_id', $contentId, ParameterType::INTEGER)
            ->setParameter('version_no', $field->versionNo, ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function load(int $contentId, ?int $version = null, ?array $translations = null): array
    {
        return $this->internalLoadContent([$contentId], $version, $translations);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadContentList(array $contentIds, ?array $translations = null): array
    {
        return $this->internalLoadContent($contentIds, null, $translations);
    }

    /**
     * Build query for the <code>load</code> and <code>loadContentList</code> methods.
     *
     * @param int[] $contentIds
     * @param string[]|null $translations a list of language codes
     *
     * @throws \Doctrine\DBAL\Exception
     *
     * @phpstan-return list<array<string,mixed>>
     *
     * @see load(), loadContentList()
     */
    private function internalLoadContent(
        array $contentIds,
        ?int $version = null,
        ?array $translations = null
    ): array {
        $queryBuilder = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $queryBuilder
            ->select(
                'c.id AS ezcontentobject_id',
                'c.contentclass_id AS ezcontentobject_contentclass_id',
                'c.section_id AS ezcontentobject_section_id',
                'c.owner_id AS ezcontentobject_owner_id',
                'c.remote_id AS ezcontentobject_remote_id',
                'c.current_version AS ezcontentobject_current_version',
                'c.initial_language_id AS ezcontentobject_initial_language_id',
                'c.modified AS ezcontentobject_modified',
                'c.published AS ezcontentobject_published',
                'c.status AS ezcontentobject_status',
                'c.name AS ezcontentobject_name',
                'c.language_mask AS ezcontentobject_language_mask',
                'c.is_hidden AS ezcontentobject_is_hidden',
                'v.id AS ezcontentobject_version_id',
                'v.version AS ezcontentobject_version_version',
                'v.modified AS ezcontentobject_version_modified',
                'v.creator_id AS ezcontentobject_version_creator_id',
                'v.created AS ezcontentobject_version_created',
                'v.status AS ezcontentobject_version_status',
                'v.language_mask AS ezcontentobject_version_language_mask',
                'v.initial_language_id AS ezcontentobject_version_initial_language_id',
                'a.id AS ezcontentobject_attribute_id',
                'a.contentclassattribute_id AS ezcontentobject_attribute_contentclassattribute_id',
                'a.data_type_string AS ezcontentobject_attribute_data_type_string',
                'a.language_code AS ezcontentobject_attribute_language_code',
                'a.language_id AS ezcontentobject_attribute_language_id',
                'a.data_float AS ezcontentobject_attribute_data_float',
                'a.data_int AS ezcontentobject_attribute_data_int',
                'a.data_text AS ezcontentobject_attribute_data_text',
                'a.sort_key_int AS ezcontentobject_attribute_sort_key_int',
                'a.sort_key_string AS ezcontentobject_attribute_sort_key_string',
                't.main_node_id AS ezcontentobject_tree_main_node_id'
            )
            ->from('ezcontentobject', 'c')
            ->innerJoin(
                'c',
                'ezcontentobject_version',
                'v',
                $expr->and(
                    $expr->eq('c.id', 'v.contentobject_id'),
                    $expr->eq('v.version', $version ?? 'c.current_version')
                )
            )
            ->innerJoin(
                'v',
                'ezcontentobject_attribute',
                'a',
                $expr->and(
                    $expr->eq('v.contentobject_id', 'a.contentobject_id'),
                    $expr->eq('v.version', 'a.version')
                )
            )
            ->leftJoin(
                'c',
                'ezcontentobject_tree',
                't',
                $expr->and(
                    $expr->eq('c.id', 't.contentobject_id'),
                    $expr->eq('t.node_id', 't.main_node_id')
                )
            );

        $queryBuilder->where(
            $expr->in(
                'c.id',
                $queryBuilder->createNamedParameter($contentIds, ArrayParameterType::INTEGER)
            )
        );

        if (!empty($translations)) {
            $queryBuilder->andWhere(
                $expr->in(
                    'a.language_code',
                    $queryBuilder->createNamedParameter($translations, ArrayParameterType::STRING)
                )
            );
        }

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadContentInfo(int $contentId): array
    {
        $queryBuilder = $this->queryBuilder->createLoadContentInfoQueryBuilder();
        $queryBuilder
            ->where('c.id = :id')
            ->setParameter('id', $contentId, ParameterType::INTEGER);

        $result = $queryBuilder->executeQuery()->fetchAssociative();
        if (false === $result) {
            throw new NotFound('content', "id: $contentId");
        }

        return $result;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadContentInfoList(array $contentIds): array
    {
        $queryBuilder = $this->queryBuilder->createLoadContentInfoQueryBuilder();
        $queryBuilder
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $contentIds, ArrayParameterType::INTEGER);

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function loadContentInfoByRemoteId(string $remoteId): array
    {
        $queryBuilder = $this->queryBuilder->createLoadContentInfoQueryBuilder();
        $queryBuilder
            ->where('c.remote_id = :id')
            ->setParameter('id', $remoteId);

        $result = $queryBuilder->executeQuery()->fetchAssociative();
        if (false === $result) {
            throw new NotFound('content', "remote_id: $remoteId");
        }

        return $result;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadContentInfoByLocationId(int $locationId): array
    {
        $queryBuilder = $this->queryBuilder->createLoadContentInfoQueryBuilder(false);
        $queryBuilder
            ->where('t.node_id = :id')
            ->setParameter('id', $locationId, ParameterType::INTEGER);

        $result = $queryBuilder->executeQuery()->fetchAssociative();
        if (false === $result) {
            throw new NotFound('content', "node_id: $locationId");
        }

        return $result;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadVersionInfo(int $contentId, ?int $versionNo = null): array
    {
        $queryBuilder = $this->queryBuilder->createVersionInfoFindQueryBuilder();
        $expr = $queryBuilder->expr();

        $queryBuilder
            ->where(
                $expr->eq(
                    'v.contentobject_id',
                    $queryBuilder->createNamedParameter(
                        $contentId,
                        ParameterType::INTEGER,
                        ':' . self::CONTENT_ID_PARAM_NAME
                    )
                )
            );

        if (null !== $versionNo) {
            $queryBuilder
                ->andWhere(
                    $expr->eq(
                        'v.version',
                        $queryBuilder->createNamedParameter(
                            $versionNo,
                            ParameterType::INTEGER,
                            ':' . self::VERSION_NO_PARAM_NAME
                        )
                    )
                );
        } else {
            $queryBuilder->andWhere($expr->eq('v.version', 'c.current_version'));
        }

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     *
     * @return array<int,array<string,mixed>>
     */
    public function loadVersionNoArchivedWithin(int $contentId, int $seconds): array
    {
        $cutoffTimestamp = time() - $seconds;
        if ($cutoffTimestamp < 0) {
            return [];
        }
        $queryBuilder = $this->queryBuilder->createVersionInfoFindQueryBuilder();
        $expr = $queryBuilder->expr();

        $queryBuilder
            ->andWhere(
                $expr->eq(
                    'v.contentobject_id',
                    $queryBuilder->createNamedParameter(
                        $contentId,
                        ParameterType::INTEGER,
                        ':content_id'
                    )
                )
            )->andWhere(
                $expr->eq(
                    'v.status',
                    $queryBuilder->createNamedParameter(
                        VersionInfo::STATUS_ARCHIVED,
                        ParameterType::INTEGER,
                        ':status'
                    )
                )
            )->andWhere(
                $expr->gt(
                    'v.modified',
                    $queryBuilder->createNamedParameter(
                        $cutoffTimestamp,
                        ParameterType::INTEGER,
                        ':modified'
                    )
                )
            )->orderBy('v.modified', 'DESC');

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    public function countVersionsForUser(int $userId, int $status = VersionInfo::STATUS_DRAFT): int
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('COUNT(v.id)')
            ->from('ezcontentobject_version', 'v')
            ->innerJoin(
                'v',
                'ezcontentobject',
                'c',
                $expr->and(
                    $expr->eq('c.id', 'v.contentobject_id'),
                    $expr->neq('c.status', ContentInfo::STATUS_TRASHED)
                )
            )
            ->where(
                $query->expr()->and(
                    $query->expr()->eq('v.status', ':' . self::STATUS_PARAM_NAME),
                    $query->expr()->eq('v.creator_id', ':' . self::USER_ID_PARAM_NAME)
                )
            )
            ->setParameter(self::STATUS_PARAM_NAME, $status, ParameterType::INTEGER)
            ->setParameter(self::USER_ID_PARAM_NAME, $userId, ParameterType::INTEGER);

        return (int) $query->executeQuery()->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function listVersionsForUser(int $userId, int $status = VersionInfo::STATUS_DRAFT): array
    {
        $query = $this->queryBuilder->createVersionInfoFindQueryBuilder();
        $query
            ->where('v.status = :status')
            ->andWhere('v.creator_id = :user_id')
            ->setParameter('status', $status, ParameterType::INTEGER)
            ->setParameter('user_id', $userId, ParameterType::INTEGER)
            ->orderBy('v.id');

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadVersionsForUser(
        int $userId,
        int $status = VersionInfo::STATUS_DRAFT,
        int $offset = 0,
        int $limit = -1
    ): array {
        $query = $this->queryBuilder->createVersionInfoFindQueryBuilder();
        $expr = $query->expr();
        $query->where(
            $expr->and(
                $expr->eq('v.status', ':' . self::STATUS_PARAM_NAME),
                $expr->eq('v.creator_id', ':' . self::USER_ID_PARAM_NAME),
                $expr->neq('c.status', ContentInfo::STATUS_TRASHED)
            )
        )
        ->setFirstResult($offset)
        ->setParameter(self::STATUS_PARAM_NAME, $status, ParameterType::INTEGER)
        ->setParameter(self::USER_ID_PARAM_NAME, $userId, ParameterType::INTEGER);

        if ($limit > 0) {
            $query->setMaxResults($limit);
        }

        $query->orderBy('v.modified', 'DESC');
        $query->addOrderBy('v.id', 'DESC');

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function listVersions(int $contentId, ?int $status = null, int $limit = -1): array
    {
        $query = $this->queryBuilder->createVersionInfoFindQueryBuilder();
        $query
            ->where('v.contentobject_id = :content_id')
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        if ($status !== null) {
            $query
                ->andWhere('v.status = :status')
                ->setParameter('status', $status);
        }

        if ($limit > 0) {
            $query->setMaxResults($limit);
        }

        $query->orderBy('v.id');

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @return int[]
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function listVersionNumbers(int $contentId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('version')
            ->from(self::CONTENT_VERSION_TABLE)
            ->where(self::CONTENT_ITEM_ID_EQ_CONTENT_ID_PARAM_COMPARISON)
            ->groupBy('version')
            ->setParameter(self::CONTENT_ID_PARAM_NAME, $contentId, ParameterType::INTEGER);

        return array_map('intval', $query->executeQuery()->fetchFirstColumn());
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getLastVersionNumber(int $contentId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('MAX(version)')
            ->from(self::CONTENT_VERSION_TABLE)
            ->where(self::CONTENT_ITEM_ID_EQ_CONTENT_ID_PARAM_COMPARISON)
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        $statement = $query->executeQuery();

        return (int)$statement->fetchOne();
    }

    /**
     * @return int[]
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getAllLocationIds(int $contentId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('node_id')
            ->from('ezcontentobject_tree')
            ->where(self::CONTENT_ITEM_ID_EQ_CONTENT_ID_PARAM_COMPARISON)
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        return $query->executeQuery()->fetchFirstColumn();
    }

    /**
     * @return int[][]
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getFieldIdsByType(
        int $contentId,
        ?int $versionNo = null,
        ?string $languageCode = null
    ): array {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('id', 'data_type_string')
            ->from(self::CONTENT_FIELD_TABLE)
            ->where(self::CONTENT_ITEM_ID_EQ_CONTENT_ID_PARAM_COMPARISON)
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        if (null !== $versionNo) {
            $query
                ->andWhere(self::VERSION_NO_EQ_VERSION_NO_PARAM_COMPARISON)
                ->setParameter('version_no', $versionNo, ParameterType::INTEGER);
        }

        if (!empty($languageCode)) {
            $query
                ->andWhere('language_code = :language_code')
                ->setParameter('language_code', $languageCode);
        }

        $statement = $query->executeQuery();

        $result = [];
        foreach ($statement->fetchAllAssociative() as $row) {
            if (!isset($result[$row['data_type_string']])) {
                $result[$row['data_type_string']] = [];
            }
            $result[$row['data_type_string']][] = (int)$row['id'];
        }

        return $result;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteRelations(int $contentId, ?int $versionNo = null): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::CONTENT_RELATION_TABLE)
            ->where('from_contentobject_id = :content_id')
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        if (null !== $versionNo) {
            $query
                ->andWhere('from_contentobject_version = :version_no')
                ->setParameter('version_no', $versionNo, ParameterType::INTEGER);
        } else {
            $query->orWhere('to_contentobject_id = :content_id');
        }

        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function removeReverseFieldRelations(int $contentId): void
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query->select('a.id', 'a.version', 'a.data_type_string', 'a.data_text')
            ->from(self::CONTENT_FIELD_TABLE, 'a')
            ->innerJoin(
                'a',
                'ezcontentobject_link',
                'l',
                $expr->and(
                    'l.from_contentobject_id = a.contentobject_id',
                    'l.from_contentobject_version = a.version',
                    'l.contentclassattribute_id = a.contentclassattribute_id'
                )
            )
            ->where('l.to_contentobject_id = :content_id')
            ->andWhere(
                $expr->gt(
                    $this->getDatabasePlatform()->getBitAndComparisonExpression(
                        'l.relation_type',
                        ':' . self::RELATION_TYPE_PARAM_NAME
                    ),
                    0
                )
            )
            ->setParameter(self::CONTENT_ID_PARAM_NAME, $contentId, ParameterType::INTEGER)
            ->setParameter(self::RELATION_TYPE_PARAM_NAME, RelationType::FIELD->value | RelationType::ASSET->value, ParameterType::INTEGER);

        $statement = $query->executeQuery();

        while ($row = $statement->fetchAssociative()) {
            /** @var array{id: int|string, version: int|string, data_type_string: string, data_text: string|null} $row */
            if ($row['data_type_string'] === 'ezobjectrelation') {
                $this->removeRelationFromRelationField($row);
            }

            if ($row['data_type_string'] === 'ezobjectrelationlist') {
                $this->removeRelationFromRelationListField($contentId, $row);
            }

            if ($row['data_type_string'] === 'ezimageasset') {
                $this->removeRelationFromAssetField($row);
            }
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function removeRelationsByFieldDefinitionId(int $fieldDefinitionId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query->delete(self::CONTENT_RELATION_TABLE)
            ->where('contentclassattribute_id = :field_definition_id')
            ->setParameter('field_definition_id', $fieldDefinitionId, ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * Update field value of RelationList field type identified by given $row data,
     * removing relations toward given $contentId.
     *
     * @param array<string, mixed> $row
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function removeRelationFromRelationListField(int $contentId, array $row): void
    {
        $document = new DOMDocument('1.0', 'utf-8');
        $document->loadXML($row['data_text']);

        $xpath = new DOMXPath($document);
        $xpathExpression = "//related-objects/relation-list/relation-item[@contentobject-id='{$contentId}']";

        $relationItems = $xpath->query($xpathExpression);
        if (false === $relationItems) {
            return;
        }
        foreach ($relationItems as $relationItem) {
            $relationItem->parentNode?->removeChild($relationItem);
        }

        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::CONTENT_FIELD_TABLE)
            ->set('data_text', ':' . self::DATA_TEXT_PARAM_NAME)
            ->setParameter('data_text', $document->saveXML())
            ->where('id = :attribute_id')
            ->andWhere(self::VERSION_NO_EQ_VERSION_NO_PARAM_COMPARISON)
            ->setParameter('attribute_id', (int)$row['id'], ParameterType::INTEGER)
            ->setParameter('version_no', (int)$row['version'], ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * Update field value of Relation field type identified by given $row data,
     * removing relation data.
     *
     * @param array<string, mixed> $row
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function removeRelationFromRelationField(array $row): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::CONTENT_FIELD_TABLE)
            ->set('data_int', ':' . self::DATA_INT_PARAM_NAME)
            ->set('sort_key_int', ':' . self::SORT_KEY_INT_PARAM_NAME)
            ->setParameter('data_int', null, ParameterType::NULL)
            ->setParameter('sort_key_int', 0, ParameterType::INTEGER)
            ->where('id = :attribute_id')
            ->andWhere(self::VERSION_NO_EQ_VERSION_NO_PARAM_COMPARISON)
            ->setParameter('attribute_id', (int)$row['id'], ParameterType::INTEGER)
            ->setParameter('version_no', (int)$row['version'], ParameterType::INTEGER);

        $query->execute();
    }

    /**
     * @param array{
     *     id: int|string,
     *     version: int|string,
     *     data_type_string: string,
     *     data_text: string|null
     * } $row
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function removeRelationFromAssetField(array $row): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::CONTENT_FIELD_TABLE)
            ->set('data_text', ':data_text')
            ->set('data_int', ':data_int')
            ->set('sort_key_int', ':sort_key_int')
            ->setParameter('data_text', null, ParameterType::NULL)
            ->setParameter('data_int', null, ParameterType::NULL)
            ->setParameter('sort_key_int', 0, ParameterType::INTEGER)
            ->andWhere('id = :attribute_id')
            ->andWhere('version = :version_no')
            ->setParameter('attribute_id', (int)$row['id'], ParameterType::INTEGER)
            ->setParameter('version_no', (int)$row['version'], ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteField(int $fieldId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::CONTENT_FIELD_TABLE)
            ->where('id = :field_id')
            ->setParameter('field_id', $fieldId, ParameterType::INTEGER)
        ;

        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteFields(int $contentId, ?int $versionNo = null): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::CONTENT_FIELD_TABLE)
            ->where(self::CONTENT_ITEM_ID_EQ_CONTENT_ID_PARAM_COMPARISON)
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        if (null !== $versionNo) {
            $query
                ->andWhere(self::VERSION_NO_EQ_VERSION_NO_PARAM_COMPARISON)
                ->setParameter('version_no', $versionNo, ParameterType::INTEGER);
        }

        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteVersions(int $contentId, ?int $versionNo = null): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::CONTENT_VERSION_TABLE)
            ->where(self::CONTENT_ITEM_ID_EQ_CONTENT_ID_PARAM_COMPARISON)
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        if (null !== $versionNo) {
            $query
                ->andWhere(self::VERSION_NO_EQ_VERSION_NO_PARAM_COMPARISON)
                ->setParameter('version_no', $versionNo, ParameterType::INTEGER);
        }

        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteNames(int $contentId, int $versionNo = null): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::CONTENT_NAME_TABLE)
            ->where(self::CONTENT_ITEM_ID_EQ_CONTENT_ID_PARAM_COMPARISON)
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        if (isset($versionNo)) {
            $query
                ->andWhere(self::CONTENT_VERSION_VERSION_NO_PARAM_NAME)
                ->setParameter('version_no', $versionNo, ParameterType::INTEGER);
        }

        $query->executeStatement();
    }

    /**
     * Query Content name table to find if a name record for the given parameters exists.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function contentNameExists(int $contentId, int $version, string $languageCode): bool
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(contentobject_id)')
            ->from(self::CONTENT_NAME_TABLE)
            ->where(self::CONTENT_ITEM_ID_EQ_CONTENT_ID_PARAM_COMPARISON)
            ->andWhere(self::CONTENT_VERSION_VERSION_NO_PARAM_NAME)
            ->andWhere('content_translation = :language_code')
            ->setParameter('content_id', $contentId, ParameterType::INTEGER)
            ->setParameter('version_no', $version, ParameterType::INTEGER)
            ->setParameter('language_code', $languageCode);

        $stmt = $query->executeQuery();

        return ((int)$stmt->fetchOne()) > 0;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function setName(int $contentId, int $version, string $name, string $languageCode): void
    {
        $language = $this->languageHandler->loadByLanguageCode($languageCode);

        $query = $this->connection->createQueryBuilder();

        // prepare parameters
        $query
            ->setParameter('name', $name)
            ->setParameter(self::CONTENT_ID_PARAM_NAME, $contentId, ParameterType::INTEGER)
            ->setParameter(self::VERSION_NO_PARAM_NAME, $version, ParameterType::INTEGER)
            ->setParameter('language_id', $language->id, ParameterType::INTEGER)
            ->setParameter(self::LANGUAGE_CODE_PARAM_NAME, $language->languageCode)
        ;

        if (!$this->contentNameExists($contentId, $version, $language->languageCode)) {
            $query
                ->insert(self::CONTENT_NAME_TABLE)
                ->values(
                    [
                        'contentobject_id' => ':' . self::CONTENT_ID_PARAM_NAME,
                        'content_version' => ':' . self::VERSION_NO_PARAM_NAME,
                        'content_translation' => ':' . self::LANGUAGE_CODE_PARAM_NAME,
                        'name' => ':name',
                        'language_id' => $this->getSetNameLanguageMaskSubQuery(),
                        'real_translation' => ':' . self::LANGUAGE_CODE_PARAM_NAME,
                    ]
                );
        } else {
            $query
                ->update(self::CONTENT_NAME_TABLE)
                ->set('name', ':name')
                ->set('language_id', $this->getSetNameLanguageMaskSubQuery())
                ->set('real_translation', ':' . self::LANGUAGE_CODE_PARAM_NAME)
                ->where(self::CONTENT_ITEM_ID_EQ_CONTENT_ID_PARAM_COMPARISON)
                ->andWhere(self::CONTENT_VERSION_VERSION_NO_PARAM_NAME)
                ->andWhere('content_translation = :language_code');
        }

        $query->executeStatement();
    }

    /**
     * Return a language sub select query for setName.
     *
     * The query generates the proper language mask at the runtime of the INSERT/UPDATE query
     * generated by setName.
     *
     * @see setName
     */
    private function getSetNameLanguageMaskSubQuery(): string
    {
        return $this->sharedGateway->getSetNameLanguageMaskSubQuery();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteContent(int $contentId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::CONTENT_ITEM_TABLE)
            ->where('id = :content_id')
            ->setParameter('content_id', $contentId, ParameterType::INTEGER)
        ;

        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadRelations(
        int $contentId,
        ?int $contentVersionNo = null,
        ?int $relationType = null
    ): array {
        $query = $this->queryBuilder->createRelationFindQueryBuilder();
        $query = $this->prepareRelationQuery($query, $contentId, $contentVersionNo, $relationType);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function countRelations(
        int $contentId,
        ?int $contentVersionNo = null,
        ?int $relationType = null
    ): int {
        $query = $this->connection->createQueryBuilder();
        $query->select('COUNT(l.id)')
            ->from(self::CONTENT_RELATION_TABLE, 'l');

        $query = $this->prepareRelationQuery($query, $contentId, $contentVersionNo, $relationType);

        return (int)$query->executeQuery()->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function listRelations(
        int $contentId,
        int $limit,
        int $offset = 0,
        ?int $contentVersionNo = null,
        ?int $relationType = null
    ): array {
        $query = $this->queryBuilder->createRelationFindQueryBuilder();
        $query = $this->prepareRelationQuery($query, $contentId, $contentVersionNo, $relationType);

        $query->setFirstResult($offset)
            ->setMaxResults($limit);

        $query->orderBy('l.id', 'DESC');

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function prepareRelationQuery(
        DoctrineQueryBuilder $query,
        int $contentId,
        ?int $contentVersionNo = null,
        ?int $relationType = null
    ): DoctrineQueryBuilder {
        $expr = $query->expr();
        $query
            ->innerJoin(
                'l',
                self::CONTENT_ITEM_TABLE,
                'c_to',
                $expr->and(
                    'l.to_contentobject_id = c_to.id',
                    'c_to.status = :status'
                )
            )
            ->andWhere(
                'l.from_contentobject_id = :content_id'
            )
            ->setParameter(
                'status',
                ContentInfo::STATUS_PUBLISHED,
                ParameterType::INTEGER
            )
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        // source version number
        if ($contentVersionNo !== null) {
            $query
                ->andWhere('l.from_contentobject_version = :version_no')
                ->setParameter('version_no', $contentVersionNo, ParameterType::INTEGER);
        } else {
            // from published version only
            $query
                ->innerJoin(
                    'c_to',
                    self::CONTENT_ITEM_TABLE,
                    'c',
                    $expr->and(
                        'c.id = l.from_contentobject_id',
                        'c.current_version = l.from_contentobject_version'
                    )
                );
        }

        // relation type
        if (null !== $relationType) {
            $query
                ->andWhere(
                    $expr->gt(
                        $this->getDatabasePlatform()->getBitAndComparisonExpression(
                            'l.relation_type',
                            ':' . self::RELATION_TYPE_PARAM_NAME
                        ),
                        0
                    )
                )
                ->setParameter(self::RELATION_TYPE_PARAM_NAME, $relationType, ParameterType::INTEGER);
        }

        return $query;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function countReverseRelations(int $toContentId, ?int $relationType = null): int
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('COUNT(l.id)')
            ->from(self::CONTENT_RELATION_TABLE, 'l')
            ->innerJoin(
                'l',
                'ezcontentobject',
                'c',
                $expr->and(
                    $expr->eq('l.from_contentobject_id', 'c.id'),
                    $expr->eq('l.from_contentobject_version', 'c.current_version'),
                    $expr->eq('c.status', ':' . self::STATUS_PARAM_NAME)
                )
            )
            ->where(
                $expr->eq('l.to_contentobject_id', ':to_content_id')
            )
            ->setParameter('to_content_id', $toContentId, ParameterType::INTEGER)
            ->setParameter('status', ContentInfo::STATUS_PUBLISHED, ParameterType::INTEGER)
        ;

        // relation type
        if ($relationType !== null) {
            $query->andWhere(
                $expr->gt(
                    $this->getDatabasePlatform()->getBitAndComparisonExpression(
                        'l.relation_type',
                        (string)$relationType
                    ),
                    0
                )
            );
        }

        return (int)$query->executeQuery()->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadReverseRelations(int $toContentId, ?int $relationType = null): array
    {
        $query = $this->queryBuilder->createRelationFindQueryBuilder();
        $expr = $query->expr();
        $query
            ->join(
                'l',
                'ezcontentobject',
                'c',
                $expr->and(
                    'c.id = l.from_contentobject_id',
                    'c.current_version = l.from_contentobject_version',
                    'c.status = :status'
                )
            )
            ->where('l.to_contentobject_id = :to_content_id')
            ->setParameter('to_content_id', $toContentId, ParameterType::INTEGER)
            ->setParameter(
                'status',
                ContentInfo::STATUS_PUBLISHED,
                ParameterType::INTEGER
            );

        // relation type
        if (null !== $relationType) {
            $query->andWhere(
                $expr->gt(
                    $this->getDatabasePlatform()->getBitAndComparisonExpression(
                        'l.relation_type',
                        ':' . self::RELATION_TYPE_PARAM_NAME
                    ),
                    0
                )
            )
                ->setParameter(self::RELATION_TYPE_PARAM_NAME, $relationType, ParameterType::INTEGER);
        }

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function listReverseRelations(
        int $toContentId,
        int $offset = 0,
        int $limit = -1,
        ?int $relationType = null
    ): array {
        $query = $this->queryBuilder->createRelationFindQueryBuilder();
        $expr = $query->expr();
        $query
            ->innerJoin(
                'l',
                'ezcontentobject',
                'c',
                $expr->and(
                    $expr->eq('l.from_contentobject_id', 'c.id'),
                    $expr->eq('l.from_contentobject_version', 'c.current_version'),
                    $expr->eq('c.status', ContentInfo::STATUS_PUBLISHED)
                )
            )
            ->where(
                $expr->eq('l.to_contentobject_id', ':toContentId')
            )
            ->setParameter('toContentId', $toContentId, ParameterType::INTEGER);

        // relation type
        if ($relationType !== null) {
            $query->andWhere(
                $expr->gt(
                    $this->getDatabasePlatform()->getBitAndComparisonExpression(
                        'l.relation_type',
                        (string)$relationType
                    ),
                    0
                )
            );
        }
        $query->setFirstResult($offset);
        if ($limit > 0) {
            $query->setMaxResults($limit);
        }
        $query->orderBy('l.id', 'DESC');

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function insertRelation(RelationCreateStruct $createStruct): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::CONTENT_RELATION_TABLE)
            ->values(
                [
                    'contentclassattribute_id' => ':' . self::FIELD_DEFINITION_ID_PARAM_NAME,
                    'from_contentobject_id' => ':from_content_id',
                    'from_contentobject_version' => ':from_version_no',
                    'relation_type' => ':' . self::RELATION_TYPE_PARAM_NAME,
                    'to_contentobject_id' => ':to_content_id',
                ]
            )
            ->setParameter(
                'field_definition_id',
                (int)$createStruct->sourceFieldDefinitionId,
                ParameterType::INTEGER
            )
            ->setParameter(
                'from_content_id',
                $createStruct->sourceContentId,
                ParameterType::INTEGER
            )
            ->setParameter(
                'from_version_no',
                $createStruct->sourceContentVersionNo,
                ParameterType::INTEGER
            )
            ->setParameter(self::RELATION_TYPE_PARAM_NAME, $createStruct->type, ParameterType::INTEGER)
            ->setParameter(
                'to_content_id',
                $createStruct->destinationContentId,
                ParameterType::INTEGER
            );

        $query->executeStatement();

        return (int)$this->connection->lastInsertId(self::CONTENT_RELATION_SEQ);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function loadRelation(int $relationId): array
    {
        $query = $this->queryBuilder->createRelationFindQueryBuilder();
        $expr = $query->expr();

        $query
            ->andWhere(
                $expr->eq('id', ':relationId')
            )
            ->setParameter('relationId', $relationId, ParameterType::INTEGER);

        $result = $query->executeQuery()->fetchAllAssociative();
        $resultCount = count($result);
        if ($resultCount === 0) {
            throw new NotFoundException('Relation', $relationId);
        }

        if ($resultCount > 1) {
            throw new LogicException('More then one row found for the relation id: ' . $relationId);
        }

        return current($result);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteRelation(int $relationId, int $type): void
    {
        // Legacy Storage stores COMMON, LINK and EMBED types using bitmask, therefore, first load
        // an existing relation type by given $relationId for comparison
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('relation_type')
            ->from(self::CONTENT_RELATION_TABLE)
            ->where(self::ID_RELATION_ID_PARAM_COMPARISON)
            ->setParameter(self::RELATION_ID_PARAM_NAME, $relationId, ParameterType::INTEGER)
        ;

        $loadedRelationType = (int)$query->executeQuery()->fetchOne();

        if ($loadedRelationType <= 0) {
            return;
        }

        $query = $this->connection->createQueryBuilder();
        // If a relation type matches then delete
        if ($loadedRelationType === $type) {
            $query
                ->delete(self::CONTENT_RELATION_TABLE)
                ->where(self::ID_RELATION_ID_PARAM_COMPARISON)
                ->setParameter(self::RELATION_ID_PARAM_NAME, $relationId, ParameterType::INTEGER)
            ;

            $query->executeStatement();
        } elseif ($loadedRelationType & $type) {
            // If relation type is composite update bitmask

            $query
                ->update(self::CONTENT_RELATION_TABLE)
                ->set(
                    'relation_type',
                    // make & operation removing given $type from the bitmask
                    $this->getDatabasePlatform()->getBitAndComparisonExpression(
                        'relation_type',
                        ':' . self::RELATION_TYPE_PARAM_NAME
                    )
                )
                // set the relation type as needed for the above & expression
                ->setParameter(self::RELATION_TYPE_PARAM_NAME, ~$type, ParameterType::INTEGER)
                ->where(self::ID_RELATION_ID_PARAM_COMPARISON)
                ->setParameter(self::RELATION_ID_PARAM_NAME, $relationId, ParameterType::INTEGER)
            ;

            $query->executeStatement();
        }
    }

    /**
     * @return int[]
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getContentIdsByContentTypeId(int $contentTypeId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('id')
            ->from(self::CONTENT_ITEM_TABLE)
            ->where('contentclass_id = :content_type_id')
            ->setParameter('content_type_id', $contentTypeId, ParameterType::INTEGER);

        $statement = $query->executeQuery();

        return array_map('intval', $statement->fetchFirstColumn());
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadVersionedNameData(array $rows): array
    {
        $query = $this->queryBuilder->createNamesQuery();
        $expr = $query->expr();
        $conditions = [];
        foreach ($rows as $row) {
            $conditions[] = $expr->and(
                $expr->eq(
                    'contentobject_id',
                    $query->createPositionalParameter($row['id'], ParameterType::INTEGER)
                ),
                $expr->eq(
                    'content_version',
                    $query->createPositionalParameter($row['version'], ParameterType::INTEGER)
                ),
            );
        }

        $query->where($expr->or(...$conditions));

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function copyRelations(
        int $originalContentId,
        int $copiedContentId,
        ?int $versionNo = null
    ): void {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select(
                'l.contentclassattribute_id',
                ':copied_id',
                'l.from_contentobject_version',
                'l.relation_type',
                'l.to_contentobject_id'
            )
            ->from(self::CONTENT_RELATION_TABLE, 'l')
            ->where('l.from_contentobject_id = :original_id')
            ->setParameter('copied_id', $copiedContentId, ParameterType::INTEGER)
            ->setParameter('original_id', $originalContentId, ParameterType::INTEGER);

        if ($versionNo) {
            $selectQuery
                ->andWhere('l.from_contentobject_version = :version')
                ->setParameter('version', $versionNo, ParameterType::INTEGER);
        }
        // Given we can retain all columns, we just create copies with new `from_contentobject_id` using INSERT INTO SELECT
        $insertQuery = <<<SQL
            INSERT INTO ezcontentobject_link (
                contentclassattribute_id,
                from_contentobject_id,
                from_contentobject_version,
                relation_type,
                to_contentobject_id
            )
            SQL;

        $insertQuery .= $selectQuery->getSQL();

        $this->connection->executeStatement(
            $insertQuery,
            $selectQuery->getParameters(),
            $selectQuery->getParameterTypes()
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteTranslationFromContent(int $contentId, string $languageCode): void
    {
        $language = $this->languageHandler->loadByLanguageCode($languageCode);

        $this->connection->beginTransaction();
        try {
            $this->deleteTranslationFromContentVersions($contentId, $language->id);
            $this->deleteTranslationFromContentNames($contentId, $languageCode);
            $this->deleteTranslationFromContentObject($contentId, $language->id);

            $this->connection->commit();
        } catch (DBALException $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteTranslatedFields(
        string $languageCode,
        int $contentId,
        ?int $versionNo = null
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete('ezcontentobject_attribute')
            ->where(self::CONTENT_ITEM_ID_EQ_CONTENT_ID_PARAM_COMPARISON)
            ->andWhere('language_code = :languageCode')
            ->setParameters(
                [
                    self::CONTENT_ID_PARAM_NAME => $contentId,
                    'languageCode' => $languageCode,
                ]
            )
        ;

        if (null !== $versionNo) {
            $query
                ->andWhere($query->expr()->and('version', ':' . self::VERSION_NO_PARAM_NAME))
                ->setParameter(self::VERSION_NO_PARAM_NAME, $versionNo)
            ;
        }

        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function deleteTranslationFromVersion(
        int $contentId,
        int $versionNo,
        string $languageCode
    ): void {
        $language = $this->languageHandler->loadByLanguageCode($languageCode);

        $this->connection->beginTransaction();
        try {
            $this->deleteTranslationFromContentVersions($contentId, $language->id, $versionNo);
            $this->deleteTranslationFromContentNames($contentId, $languageCode, $versionNo);

            $this->connection->commit();
        } catch (DBALException $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Delete translation from the ezcontentobject_name table.
     *
     * @param int|null $versionNo optional, if specified, apply to this Version only.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function deleteTranslationFromContentNames(
        int $contentId,
        string $languageCode,
        ?int $versionNo = null
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete('ezcontentobject_name')
            ->where('contentobject_id=:contentId')
            ->andWhere('real_translation=:languageCode')
            ->setParameters(
                [
                    'languageCode' => $languageCode,
                    'contentId' => $contentId,
                ]
            )
        ;

        if (null !== $versionNo) {
            $query
                ->andWhere($query->expr()->and('content_version', ':' . self::VERSION_NO_PARAM_NAME))
                ->setParameter(self::VERSION_NO_PARAM_NAME, $versionNo)
            ;
        }

        $query->executeStatement();
    }

    /**
     * Remove language from language_mask of ezcontentobject.
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    private function deleteTranslationFromContentObject(int $contentId, int $languageId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query->update('ezcontentobject')
            // parameter for bitwise operation has to be placed verbatim (w/o binding) for this to work cross-DBMS
            ->set('language_mask', $this->getLanguageRemovalFromLanguageMaskExpression($languageId))
            ->set('modified', ':now')
            ->where('id = :content_id')
            ->andWhere(
                // make sure removed translation is not the last one (incl. alwaysAvailable)
                $query->expr()->and(
                    $this->getLanguageRemovalFromLanguageMaskExpression($languageId) . ' <> 0',
                    $this->getLanguageRemovalFromLanguageMaskExpression($languageId) . ' <> 1'
                )
            )
            ->setParameter('now', time())
            ->setParameter(self::CONTENT_ID_PARAM_NAME, $contentId)
        ;

        $rowCount = $query->executeStatement();

        // no rows updated means that most likely somehow it was the last remaining translation
        if ($rowCount === 0) {
            throw new BadStateException(
                '$languageCode',
                'The provided translation is the only translation in this version'
            );
        }
    }

    /**
     * Remove language from language_mask of ezcontentobject_version and update initialLanguageId
     * if it matches the removed one.
     *
     * @param int|null $versionNo optional, if specified, apply to this Version only.
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    private function deleteTranslationFromContentVersions(
        int $contentId,
        int $languageId,
        ?int $versionNo = null
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query->update('ezcontentobject_version')
            // parameter for bitwise operation has to be placed verbatim (w/o binding) for this to work cross-DBMS
            ->set('language_mask', $this->getLanguageRemovalFromLanguageMaskExpression($languageId))
            ->set('modified', ':now')
            // update initial_language_id only if it matches removed translation languageId
            ->set(
                'initial_language_id',
                'CASE WHEN initial_language_id = :languageId ' .
                'THEN (SELECT initial_language_id AS main_language_id FROM ezcontentobject c WHERE c.id = :content_id) ' .
                'ELSE initial_language_id END'
            )
            ->where(self::CONTENT_ITEM_ID_EQ_CONTENT_ID_PARAM_COMPARISON)
            ->andWhere(
                // make sure removed translation is not the last one (incl. alwaysAvailable)
                $query->expr()->and(
                    $this->getLanguageRemovalFromLanguageMaskExpression($languageId) . ' <> 0',
                    $this->getLanguageRemovalFromLanguageMaskExpression($languageId) . ' <> 1'
                )
            )
            ->setParameter('now', time())
            ->setParameter('content_id', $contentId)
            ->setParameter('languageId', $languageId)
        ;

        if (null !== $versionNo) {
            $query
                ->andWhere($query->expr()->and('version', ':' . self::VERSION_NO_PARAM_NAME))
                ->setParameter(self::VERSION_NO_PARAM_NAME, $versionNo)
            ;
        }

        $rowCount = $query->executeStatement();

        // no rows updated means that most likely somehow it was the last remaining translation
        if ($rowCount === 0) {
            throw new BadStateException(
                '$languageCode',
                'The provided translation is the only translation in this version'
            );
        }
    }

    /**
     * Compute language mask and append it to a QueryBuilder (both column and parameter).
     *
     * **Can be used on UPDATE queries only!**
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function setLanguageMaskForUpdateQuery(
        bool $alwaysAvailable,
        DoctrineQueryBuilder $query,
        string $languageMaskColumnName
    ): DoctrineQueryBuilder {
        if ($alwaysAvailable) {
            $languageMaskExpr = $this->getDatabasePlatform()->getBitOrComparisonExpression(
                $languageMaskColumnName,
                ':' . self::LANGUAGE_MASK_OPERAND_PARAM_NAME
            );
        } else {
            $languageMaskExpr = $this->getDatabasePlatform()->getBitAndComparisonExpression(
                $languageMaskColumnName,
                ':' . self::LANGUAGE_MASK_OPERAND_PARAM_NAME
            );
        }

        $query
            ->set($languageMaskColumnName, $languageMaskExpr)
            ->setParameter(
                self::LANGUAGE_MASK_OPERAND_PARAM_NAME,
                $alwaysAvailable ? 1 : self::REMOVE_ALWAYS_AVAILABLE_LANG_MASK_OPERAND
            );

        return $query;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadVersionInfoList(array $contentIds): array
    {
        $queryBuilder = $this->queryBuilder->createVersionInfoFindQueryBuilder();
        $expr = $queryBuilder->expr();

        $queryBuilder
            ->andWhere(
                $expr->in(
                    'c.id',
                    $queryBuilder->createNamedParameter($contentIds, ArrayParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq('v.version', 'c.current_version')
            );

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    private function getLanguageRemovalFromLanguageMaskExpression(int $languageId): string
    {
        return 'language_mask & ~ ' . $languageId;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function getDatabasePlatform(): AbstractPlatform
    {
        if (!isset($this->databasePlatform)) {
            $databasePlatform = $this->connection->getDatabasePlatform();
            if (null === $databasePlatform) {
                throw new LogicException('Unable to fetch database platform');
            }
            $this->databasePlatform = $databasePlatform;
        }

        return $this->databasePlatform;
    }
}
