<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\MetadataUpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct as RelationCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\UpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Base\Exceptions\NotFoundException as NotFound;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Gateway\DoctrineDatabase\QueryBuilder;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use Ibexa\Core\Persistence\Legacy\SharedGateway\Gateway as SharedGateway;
use LogicException;
use RuntimeException;

/**
 * Doctrine database based content gateway.
 *
 * @internal Gateway implementation is considered internal. Use Persistence Content Handler instead.
 *
 * @see \Ibexa\Contracts\Core\Persistence\Content\Handler
 */
final class DoctrineDatabase extends Gateway
{
    public function __construct(
        protected Connection $connection,
        private readonly SharedGateway $sharedGateway,
        protected QueryBuilder $queryBuilder,
        protected LanguageHandler $languageHandler
    ) {
    }

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
                    'content_type_id' => $query->createPositionalParameter(
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
                    'always_available' => $query->createPositionalParameter(
                        $struct->alwaysAvailable,
                        ParameterType::BOOLEAN
                    ),
                ]
            );

        $query->executeStatement();

        $contentId = (int)$this->connection->lastInsertId();
        $this->insertContentTranslations(
            $contentId,
            $this->collectFieldLanguageCodes($struct->fields, $initialLanguageCode)
        );

        return $contentId;
    }

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
                    'always_available' => $query->createPositionalParameter(
                        $versionInfo->contentInfo->alwaysAvailable,
                        ParameterType::BOOLEAN
                    ),
                ]
            );

        $query->executeStatement();

        $versionId = (int)$this->connection->lastInsertId();
        $this->insertVersionTranslations(
            $versionId,
            $this->collectFieldLanguageCodes($fields, $versionInfo->initialLanguageCode)
        );

        return $versionId;
    }

    /**
     * Collects the unique language codes a Content/Version's fields are written in, always
     * including $initialLanguageCode, for populating "ibexa_content_translation"/
     * "ibexa_content_version_translation".
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field[] $fields
     *
     * @return string[]
     */
    private function collectFieldLanguageCodes(array $fields, string $initialLanguageCode): array
    {
        $languageCodes = [$initialLanguageCode => true];
        foreach ($fields as $field) {
            $languageCodes[$field->languageCode] = true;
        }

        return array_keys($languageCodes);
    }

    /**
     * @param string[] $languageCodes
     */
    private function insertContentTranslations(int $contentId, array $languageCodes): void
    {
        foreach (array_unique($languageCodes) as $languageCode) {
            $languageId = $this->languageHandler->loadByLanguageCode($languageCode)->id;
            $this->connection->executeStatement(
                'INSERT INTO ibexa_content_translation (content_id, language_id) VALUES (:contentId, :languageId)',
                ['contentId' => $contentId, 'languageId' => $languageId],
                ['contentId' => ParameterType::INTEGER, 'languageId' => ParameterType::INTEGER]
            );
        }
    }

    /**
     * @param string[] $languageCodes
     */
    private function insertVersionTranslations(int $versionId, array $languageCodes): void
    {
        foreach (array_unique($languageCodes) as $languageCode) {
            $languageId = $this->languageHandler->loadByLanguageCode($languageCode)->id;
            $this->connection->executeStatement(
                'INSERT INTO ibexa_content_version_translation (content_version_id, language_id) VALUES (:versionId, :languageId)',
                ['versionId' => $versionId, 'languageId' => $languageId],
                ['versionId' => ParameterType::INTEGER, 'languageId' => ParameterType::INTEGER]
            );
        }
    }

    /**
     * Adds $languageCodes to Version $versionId's translations, leaving any already present
     * untouched - mirrors updateVersion()'s additive bit-OR merge onto "language_mask".
     *
     * @param string[] $languageCodes
     */
    private function addVersionTranslationsIfMissing(int $versionId, array $languageCodes): void
    {
        foreach (array_unique($languageCodes) as $languageCode) {
            $languageId = $this->languageHandler->loadByLanguageCode($languageCode)->id;
            $this->connection->executeStatement(
                'INSERT INTO ibexa_content_version_translation (content_version_id, language_id)
                 SELECT :versionId, :languageId
                 WHERE NOT EXISTS (
                     SELECT 1 FROM ibexa_content_version_translation
                     WHERE content_version_id = :versionId AND language_id = :languageId
                 )',
                ['versionId' => $versionId, 'languageId' => $languageId],
                ['versionId' => ParameterType::INTEGER, 'languageId' => ParameterType::INTEGER]
            );
        }
    }

    /**
     * Replaces Content $contentId's translations with exactly $languageCodes - mirrors
     * updateContent()'s publish-time replace of "language_mask" with the newly published
     * version's language set (not a union with whatever the content previously had).
     *
     * @param string[] $languageCodes
     */
    private function replaceContentTranslations(int $contentId, array $languageCodes): void
    {
        $this->connection->executeStatement(
            'DELETE FROM ibexa_content_translation WHERE content_id = :contentId',
            ['contentId' => $contentId],
            ['contentId' => ParameterType::INTEGER]
        );

        $this->insertContentTranslations($contentId, $languageCodes);
    }

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

        $hasSetClause = false;

        foreach ($fieldsForUpdateMap as $fieldName => $field) {
            if (null === $field['value']) {
                continue;
            }
            $query->set(
                $fieldName,
                $query->createNamedParameter($field['value'], $field['type'], ":{$fieldName}")
            );
            $hasSetClause = true;
        }

        if ($prePublishVersionInfo !== null) {
            $alwaysAvailable = $struct->alwaysAvailable ?? $prePublishVersionInfo->contentInfo->alwaysAvailable;
            $query->set(
                'always_available',
                $query->createNamedParameter($alwaysAvailable, ParameterType::BOOLEAN, ':alwaysAvailable')
            );
            $hasSetClause = true;
        }

        $query->where(
            $query->expr()->eq(
                'id',
                $query->createNamedParameter($contentId, ParameterType::INTEGER, ':contentId')
            )
        );

        if ($hasSetClause) {
            $query->executeStatement();
        }

        // Handle alwaysAvailable flag update separately as it's a more complex task and has impact on several tables
        if (isset($struct->alwaysAvailable) || isset($struct->mainLanguageId)) {
            $this->updateAlwaysAvailableFlag($contentId, $struct->alwaysAvailable);
        }

        if ($prePublishVersionInfo !== null) {
            $this->replaceContentTranslations($contentId, $prePublishVersionInfo->languageCodes);
        }
    }

    /**
     * Updates version $versionNo for content identified by $contentId, in respect to $struct.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function updateVersion(int $contentId, int $versionNo, UpdateStruct $struct): void
    {
        $query = $this->connection->createQueryBuilder();

        $query
            ->update(self::CONTENT_VERSION_TABLE)
            ->set('creator_id', ':creator_id')
            ->set('modified', ':modified')
            ->set('initial_language_id', ':initial_language_id')
            ->setParameter('creator_id', $struct->creatorId, ParameterType::INTEGER)
            ->setParameter('modified', $struct->modificationDate, ParameterType::INTEGER)
            ->setParameter(
                'initial_language_id',
                $struct->initialLanguageId,
                ParameterType::INTEGER
            )
            ->where('contentobject_id = :content_id')
            ->andWhere('version = :version_no')
            ->setParameter('content_id', $contentId, ParameterType::INTEGER)
            ->setParameter('version_no', $versionNo, ParameterType::INTEGER);

        $query->executeStatement();

        $versionId = $this->connection->createQueryBuilder()
            ->select('id')
            ->from(self::CONTENT_VERSION_TABLE)
            ->where('contentobject_id = :content_id')
            ->andWhere('version = :version_no')
            ->setParameter('content_id', $contentId, ParameterType::INTEGER)
            ->setParameter('version_no', $versionNo, ParameterType::INTEGER)
            ->executeQuery()
            ->fetchOne();

        $this->addVersionTranslationsIfMissing(
            (int)$versionId,
            $this->collectFieldLanguageCodes(
                $struct->fields,
                $this->languageHandler->load($struct->initialLanguageId)->languageCode
            )
        );
    }

    public function updateAlwaysAvailableFlag(int $contentId, ?bool $alwaysAvailable = null): void
    {
        if (!isset($alwaysAvailable)) {
            $contentInfoRow = $this->loadContentInfo($contentId);
            $alwaysAvailable = (bool)$contentInfoRow['always_available'];
        }

        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->update(self::CONTENT_ITEM_TABLE)
            ->set(
                'always_available',
                $query->createNamedParameter($alwaysAvailable, ParameterType::BOOLEAN, ':alwaysAvailable')
            )
            ->where(
                $expr->eq(
                    'id',
                    $query->createNamedParameter($contentId, ParameterType::INTEGER, ':contentId')
                )
            );
        $query->executeStatement();
    }

    public function setStatus(int $contentId, int $version, int $status): bool
    {
        if ($status !== APIVersionInfo::STATUS_PUBLISHED) {
            $query = $this->queryBuilder->getSetVersionStatusQuery($contentId, $version, $status);
            $rowCount = $query->executeStatement();

            return $rowCount > 0;
        } else {
            // If the version's status is PUBLISHED, we use dedicated method for publishing
            $this->setPublishedStatus($contentId, $version);

            return true;
        }
    }

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
                    SELECT 1 FROM ibexa_content_version
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

    private function markContentAsPublished(int $contentId, int $versionNo): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(Gateway::CONTENT_ITEM_TABLE)
            ->set('status', ':status')
            ->set('current_version', ':versionNo')
            ->where('id =:contentId')
            ->setParameter('status', ContentInfo::STATUS_PUBLISHED, ParameterType::INTEGER)
            ->setParameter('versionNo', $versionNo, ParameterType::INTEGER)
            ->setParameter('contentId', $contentId, ParameterType::INTEGER);
        $query->executeStatement();
    }

    /**
     * @return int ID
     *
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

        return (int)$this->sharedGateway->getLastInsertedId(self::CONTENT_FIELD_SEQ);
    }

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
     * Set the given query field (ibexa_content_field) values.
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
                    'contentobject_id' => ':content_id',
                    'content_type_field_definition_id' => ':field_definition_id',
                    'data_type_string' => ':data_type_string',
                    'language_code' => ':language_code',
                    'version' => ':version_no',
                    'data_float' => ':data_float',
                    'data_int' => ':data_int',
                    'data_text' => ':data_text',
                    'sort_key_int' => ':sort_key_int',
                    'sort_key_string' => ':sort_key_string',
                    'language_id' => ':language_id',
                ]
            )
            ->setParameter(
                'content_id',
                $content->versionInfo->contentInfo->id,
                ParameterType::INTEGER
            )
            ->setParameter('field_definition_id', $field->fieldDefinitionId, ParameterType::INTEGER)
            ->setParameter('data_type_string', $field->type, ParameterType::STRING)
            ->setParameter('language_code', $field->languageCode, ParameterType::STRING)
            ->setParameter('version_no', $field->versionNo, ParameterType::INTEGER)
            ->setParameter('data_float', $value->dataFloat)
            ->setParameter('data_int', $value->dataInt, ParameterType::INTEGER)
            ->setParameter('data_text', $value->dataText, ParameterType::STRING)
            ->setParameter('sort_key_int', $value->sortKeyInt, ParameterType::INTEGER)
            ->setParameter(
                'sort_key_string',
                mb_substr((string)$value->sortKeyString, 0, 255),
                ParameterType::STRING
            )
            ->setParameter(
                'language_id',
                $this->languageHandler->loadByLanguageCode($field->languageCode)->id,
                ParameterType::INTEGER
            );
    }

    public function updateField(Field $field, StorageFieldValue $value): void
    {
        // Note, no need to care for language_id here, since Content->$alwaysAvailable
        // cannot change on update
        $query = $this->connection->createQueryBuilder();
        $this->setFieldUpdateValues($query, $value);
        $query
            ->where('id = :field_id')
            ->andWhere('version = :version_no')
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
            ->set('data_float', ':data_float')
            ->set('data_int', ':data_int')
            ->set('data_text', ':data_text')
            ->set('sort_key_int', ':sort_key_int')
            ->set('sort_key_string', ':sort_key_string')
            ->setParameter('data_float', $value->dataFloat)
            ->setParameter('data_int', $value->dataInt, ParameterType::INTEGER)
            ->setParameter('data_text', $value->dataText, ParameterType::STRING)
            ->setParameter('sort_key_int', $value->sortKeyInt, ParameterType::INTEGER)
            ->setParameter('sort_key_string', mb_substr((string)$value->sortKeyString, 0, 255))
        ;
    }

    /**
     * Update an existing, non-translatable field.
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
            ->where('content_type_field_definition_id = :field_definition_id')
            ->andWhere('contentobject_id = :content_id')
            ->andWhere('version = :version_no')
            ->setParameter('field_definition_id', $field->fieldDefinitionId, ParameterType::INTEGER)
            ->setParameter('content_id', $contentId, ParameterType::INTEGER)
            ->setParameter('version_no', $field->versionNo, ParameterType::INTEGER);

        $query->executeStatement();
    }

    public function load(int $contentId, ?int $version = null, ?array $translations = null): array
    {
        return $this->internalLoadContent([$contentId], $version, $translations);
    }

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
                'c.id AS content_id',
                'c.content_type_id AS content_content_type_id',
                'c.section_id AS content_section_id',
                'c.owner_id AS content_owner_id',
                'c.remote_id AS content_remote_id',
                'c.current_version AS content_current_version',
                'c.initial_language_id AS content_initial_language_id',
                'c.modified AS content_modified',
                'c.published AS content_published',
                'c.status AS content_status',
                'c.name AS content_name',
                'c.always_available AS content_always_available',
                'c.is_hidden AS content_is_hidden',
                'v.id AS content_version_id',
                'v.version AS content_version_version',
                'v.modified AS content_version_modified',
                'v.creator_id AS content_version_creator_id',
                'v.created AS content_version_created',
                'v.status AS content_version_status',
                'v.always_available AS content_version_always_available',
                'v.initial_language_id AS content_version_initial_language_id',
                'a.id AS content_field_id',
                'a.content_type_field_definition_id AS content_field_content_type_field_definition_id',
                'a.data_type_string AS content_field_data_type_string',
                'a.language_code AS content_field_language_code',
                'a.language_id AS content_field_language_id',
                'a.data_float AS content_field_data_float',
                'a.data_int AS content_field_data_int',
                'a.data_text AS content_field_data_text',
                'a.sort_key_int AS content_field_sort_key_int',
                'a.sort_key_string AS content_field_sort_key_string',
                't.main_node_id AS content_tree_main_node_id'
            )
            ->from(Gateway::CONTENT_ITEM_TABLE, 'c')
            ->innerJoin(
                'c',
                Gateway::CONTENT_VERSION_TABLE,
                'v',
                $expr->and(
                    $expr->eq('c.id', 'v.contentobject_id'),
                    $expr->eq('v.version', $version ?? 'c.current_version')
                )
            )
            ->innerJoin(
                'v',
                Gateway::CONTENT_FIELD_TABLE,
                'a',
                $expr->and(
                    $expr->eq('v.contentobject_id', 'a.contentobject_id'),
                    $expr->eq('v.version', 'a.version')
                )
            )
            ->leftJoin(
                'c',
                LocationGateway::CONTENT_TREE_TABLE,
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

    public function loadContentInfo(int $contentId): array
    {
        $queryBuilder = $this->queryBuilder->createLoadContentInfoQueryBuilder();
        $queryBuilder
            ->where('c.id = :id')
            ->setParameter('id', $contentId, ParameterType::INTEGER);

        $results = $queryBuilder->executeQuery()->fetchAllAssociative();
        if (empty($results)) {
            throw new NotFound('content', "id: $contentId");
        }

        return $results[0];
    }

    public function loadContentInfoList(array $contentIds): array
    {
        $queryBuilder = $this->queryBuilder->createLoadContentInfoQueryBuilder();
        $queryBuilder
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $contentIds, ArrayParameterType::INTEGER);

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    public function loadContentInfoByRemoteId(string $remoteId): array
    {
        $queryBuilder = $this->queryBuilder->createLoadContentInfoQueryBuilder();
        $queryBuilder
            ->where('c.remote_id = :id')
            ->setParameter('id', $remoteId, ParameterType::STRING);

        $results = $queryBuilder->executeQuery()->fetchAllAssociative();
        if (empty($results)) {
            throw new NotFound('content', "remote_id: $remoteId");
        }

        return $results[0];
    }

    public function loadContentInfoByLocationId(int $locationId): array
    {
        $queryBuilder = $this->queryBuilder->createLoadContentInfoQueryBuilder(false);
        $queryBuilder
            ->where('t.node_id = :id')
            ->setParameter('id', $locationId, ParameterType::INTEGER);

        $results = $queryBuilder->executeQuery()->fetchAllAssociative();
        if (empty($results)) {
            throw new NotFound('content', "node_id: $locationId");
        }

        return $results[0];
    }

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
                        ':content_id'
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
                            ':version_no'
                        )
                    )
                );
        } else {
            $queryBuilder->andWhere($expr->eq('v.version', 'c.current_version'));
        }

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
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
            ->from(Gateway::CONTENT_VERSION_TABLE, 'v')
            ->innerJoin(
                'v',
                Gateway::CONTENT_ITEM_TABLE,
                'c',
                $expr->and(
                    $expr->eq('c.id', 'v.contentobject_id'),
                    $expr->neq('c.status', ContentInfo::STATUS_TRASHED)
                )
            )
            ->where(
                $query->expr()->and(
                    $query->expr()->eq('v.status', ':status'),
                    $query->expr()->eq('v.creator_id', ':user_id')
                )
            )
            ->setParameter('status', $status, ParameterType::INTEGER)
            ->setParameter('user_id', $userId, ParameterType::INTEGER);

        return (int) $query->executeQuery()->fetchOne();
    }

    /**
     * Return data for all versions with the given status created by the given $userId.
     *
     * @return string[][]
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
                $expr->eq('v.status', ':status'),
                $expr->eq('v.creator_id', ':user_id'),
                $expr->neq('c.status', ContentInfo::STATUS_TRASHED)
            )
        )
        ->setFirstResult($offset)
        ->setParameter('status', $status, ParameterType::INTEGER)
        ->setParameter('user_id', $userId, ParameterType::INTEGER);

        if ($limit > 0) {
            $query->setMaxResults($limit);
        }

        $query->orderBy('v.modified', 'DESC');
        $query->addOrderBy('v.id', 'DESC');

        return $query->executeQuery()->fetchAllAssociative();
    }

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
     */
    public function listVersionNumbers(int $contentId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('version')
            ->from(self::CONTENT_VERSION_TABLE)
            ->where('contentobject_id = :contentId')
            ->groupBy('version')
            ->setParameter('contentId', $contentId, ParameterType::INTEGER);

        return array_map('intval', $query->executeQuery()->fetchFirstColumn());
    }

    public function getLastVersionNumber(int $contentId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('MAX(version)')
            ->from(self::CONTENT_VERSION_TABLE)
            ->where('contentobject_id = :content_id')
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        $statement = $query->executeQuery();

        return (int)$statement->fetchOne();
    }

    /**
     * @return int[]
     */
    public function getAllLocationIds(int $contentId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('node_id')
            ->from(LocationGateway::CONTENT_TREE_TABLE)
            ->where('contentobject_id = :content_id')
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        return $query->executeQuery()->fetchFirstColumn();
    }

    /**
     * @return int[][]
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
            ->where('contentobject_id = :content_id')
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        if (null !== $versionNo) {
            $query
                ->andWhere('version = :version_no')
                ->setParameter('version_no', $versionNo, ParameterType::INTEGER);
        }

        if (!empty($languageCode)) {
            $query
                ->andWhere('language_code = :language_code')
                ->setParameter('language_code', $languageCode, ParameterType::STRING);
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

    public function removeReverseFieldRelations(int $contentId): void
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('a.id', 'a.version', 'a.data_type_string', 'a.data_text')
            ->from(self::CONTENT_FIELD_TABLE, 'a')
            ->innerJoin(
                'a',
                Gateway::CONTENT_RELATION_TABLE,
                'l',
                $expr->and(
                    'l.from_contentobject_id = a.contentobject_id',
                    'l.from_contentobject_version = a.version',
                    'l.content_type_field_definition_id = a.content_type_field_definition_id'
                )
            )
            ->where('l.to_contentobject_id = :content_id')
            ->andWhere(
                $expr->gt(
                    $this->getDatabasePlatform()->getBitAndComparisonExpression(
                        'l.relation_type',
                        ':relation_type'
                    ),
                    0
                )
            )
            ->setParameter('content_id', $contentId, ParameterType::INTEGER)
            ->setParameter('relation_type', RelationType::FIELD->value | RelationType::ASSET->value, ParameterType::INTEGER);

        $statement = $query->executeQuery();

        while ($row = $statement->fetchAssociative()) {
            if ($row['data_type_string'] === 'ibexa_object_relation') {
                $this->removeRelationFromRelationField($row);
            }

            if ($row['data_type_string'] === 'ibexa_object_relation_list') {
                $this->removeRelationFromRelationListField($contentId, $row);
            }

            if ($row['data_type_string'] === 'ibexa_image_asset') {
                $this->removeRelationFromAssetField($row);
            }
        }
    }

    public function removeRelationsByFieldDefinitionId(int $fieldDefinitionId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query->delete(self::CONTENT_RELATION_TABLE)
            ->where('content_type_field_definition_id = :field_definition_id')
            ->setParameter('field_definition_id', $fieldDefinitionId, ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * Update field value of RelationList field type identified by given $row data,
     * removing relations toward given $contentId.
     *
     * @param array $row
     */
    private function removeRelationFromRelationListField(int $contentId, array $row): void
    {
        $document = new DOMDocument('1.0', 'utf-8');
        $document->loadXML($row['data_text']);

        $xpath = new DOMXPath($document);
        $xpathExpression = "//related-objects/relation-list/relation-item[@contentobject-id='{$contentId}']";

        $relationItems = $xpath->query($xpathExpression);
        if ($relationItems === false) {
            throw new RuntimeException('Invalid XPath: ' . $xpathExpression);
        }

        foreach ($relationItems as $relationItem) {
            if ($relationItem instanceof DOMElement && $relationItem->parentNode !== null) {
                $relationItem->parentNode->removeChild($relationItem);
            }
        }

        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::CONTENT_FIELD_TABLE)
            ->set('data_text', ':data_text')
            ->setParameter('data_text', $document->saveXML(), ParameterType::STRING)
            ->where('id = :attribute_id')
            ->andWhere('version = :version_no')
            ->setParameter('attribute_id', (int)$row['id'], ParameterType::INTEGER)
            ->setParameter('version_no', (int)$row['version'], ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * Update field value of Relation field type identified by given $row data,
     * removing relation data.
     *
     * @param array $row
     */
    private function removeRelationFromRelationField(array $row): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::CONTENT_FIELD_TABLE)
            ->set('data_int', ':data_int')
            ->set('sort_key_int', ':sort_key_int')
            ->setParameter('data_int', null, ParameterType::NULL)
            ->setParameter('sort_key_int', 0, ParameterType::INTEGER)
            ->where('id = :attribute_id')
            ->andWhere('version = :version_no')
            ->setParameter('attribute_id', (int)$row['id'], ParameterType::INTEGER)
            ->setParameter('version_no', (int)$row['version'], ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * @param array<string, mixed> $row
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

    public function deleteFields(int $contentId, ?int $versionNo = null): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::CONTENT_FIELD_TABLE)
            ->where('contentobject_id = :content_id')
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        if (null !== $versionNo) {
            $query
                ->andWhere('version = :version_no')
                ->setParameter('version_no', $versionNo, ParameterType::INTEGER);
        }

        $query->executeStatement();
    }

    public function deleteVersions(int $contentId, ?int $versionNo = null): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::CONTENT_VERSION_TABLE)
            ->where('contentobject_id = :content_id')
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        if (null !== $versionNo) {
            $query
                ->andWhere('version = :version_no')
                ->setParameter('version_no', $versionNo, ParameterType::INTEGER);
        }

        $query->executeStatement();
    }

    public function deleteNames(int $contentId, ?int $versionNo = null): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::CONTENT_NAME_TABLE)
            ->where('contentobject_id = :content_id')
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        if (isset($versionNo)) {
            $query
                ->andWhere('content_version = :version_no')
                ->setParameter('version_no', $versionNo, ParameterType::INTEGER);
        }

        $query->executeStatement();
    }

    /**
     * Query Content name table to find if a name record for the given parameters exists.
     */
    private function contentNameExists(int $contentId, int $version, string $languageCode): bool
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(contentobject_id)')
            ->from(self::CONTENT_NAME_TABLE)
            ->where('contentobject_id = :content_id')
            ->andWhere('content_version = :version_no')
            ->andWhere('content_translation = :language_code')
            ->setParameter('content_id', $contentId, ParameterType::INTEGER)
            ->setParameter('version_no', $version, ParameterType::INTEGER)
            ->setParameter('language_code', $languageCode, ParameterType::STRING);

        $stmt = $query->executeQuery();

        return (int)$stmt->fetchOne() > 0;
    }

    public function setName(int $contentId, int $version, string $name, string $languageCode): void
    {
        $language = $this->languageHandler->loadByLanguageCode($languageCode);

        $query = $this->connection->createQueryBuilder();

        // prepare parameters
        $query
            ->setParameter('name', $name, ParameterType::STRING)
            ->setParameter('content_id', $contentId, ParameterType::INTEGER)
            ->setParameter('version_no', $version, ParameterType::INTEGER)
            ->setParameter('language_id', $language->id, ParameterType::INTEGER)
            ->setParameter('language_code', $language->languageCode, ParameterType::STRING)
        ;

        if (!$this->contentNameExists($contentId, $version, $language->languageCode)) {
            $query
                ->insert(self::CONTENT_NAME_TABLE)
                ->values(
                    [
                        'contentobject_id' => ':content_id',
                        'content_version' => ':version_no',
                        'content_translation' => ':language_code',
                        'name' => ':name',
                        'language_id' => ':language_id',
                        'real_translation' => ':language_code',
                    ]
                );
        } else {
            $query
                ->update(self::CONTENT_NAME_TABLE)
                ->set('name', ':name')
                ->set('language_id', ':language_id')
                ->set('real_translation', ':language_code')
                ->where('contentobject_id = :content_id')
                ->andWhere('content_version = :version_no')
                ->andWhere('content_translation = :language_code');
        }

        $query->executeStatement();
    }

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

    public function loadRelations(
        int $contentId,
        ?int $contentVersionNo = null,
        ?int $relationType = null
    ): array {
        $query = $this->queryBuilder->createRelationFindQueryBuilder();
        $query = $this->prepareRelationQuery($query, $contentId, $contentVersionNo, $relationType);

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function countRelations(
        int $contentId,
        ?int $contentVersionNo = null,
        ?int $relationType = null
    ): int {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(l.id)')
            ->from(self::CONTENT_RELATION_TABLE, 'l');

        $query = $this->prepareRelationQuery($query, $contentId, $contentVersionNo, $relationType);

        return (int)$query->executeQuery()->fetchOne();
    }

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
                            ':relation_type'
                        ),
                        0
                    )
                )
                ->setParameter('relation_type', $relationType, ParameterType::INTEGER);
        }

        return $query;
    }

    public function countReverseRelations(int $toContentId, ?int $relationType = null): int
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('COUNT(l.id)')
            ->from(self::CONTENT_RELATION_TABLE, 'l')
            ->innerJoin(
                'l',
                Gateway::CONTENT_ITEM_TABLE,
                'c',
                $expr->and(
                    $expr->eq('l.from_contentobject_id', 'c.id'),
                    $expr->eq('l.from_contentobject_version', 'c.current_version'),
                    $expr->eq('c.status', ':status')
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
                        $relationType
                    ),
                    0
                )
            );
        }

        return (int)$query->executeQuery()->fetchOne();
    }

    public function loadReverseRelations(int $toContentId, ?int $relationType = null): array
    {
        $query = $this->queryBuilder->createRelationFindQueryBuilder();
        $expr = $query->expr();
        $query
            ->join(
                'l',
                Gateway::CONTENT_ITEM_TABLE,
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
                        ':relation_type'
                    ),
                    0
                )
            )
                ->setParameter('relation_type', $relationType, ParameterType::INTEGER);
        }

        return $query->executeQuery()->fetchAllAssociative();
    }

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
                Gateway::CONTENT_ITEM_TABLE,
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
                        $relationType
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

    public function insertRelation(RelationCreateStruct $createStruct): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::CONTENT_RELATION_TABLE)
            ->values(
                [
                    'content_type_field_definition_id' => ':field_definition_id',
                    'from_contentobject_id' => ':from_content_id',
                    'from_contentobject_version' => ':from_version_no',
                    'relation_type' => ':relation_type',
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
            ->setParameter('relation_type', $createStruct->type, ParameterType::INTEGER)
            ->setParameter(
                'to_content_id',
                $createStruct->destinationContentId,
                ParameterType::INTEGER
            );

        $query->executeStatement();

        return (int)$this->connection->lastInsertId();
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
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

    public function deleteRelation(int $relationId, int $type): void
    {
        // Legacy Storage stores COMMON, LINK and EMBED types using bitmask, therefore first load
        // existing relation type by given $relationId for comparison
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('relation_type')
            ->from(self::CONTENT_RELATION_TABLE)
            ->where('id = :relation_id')
            ->setParameter('relation_id', $relationId, ParameterType::INTEGER)
        ;

        $loadedRelationType = $query->executeQuery()->fetchOne();

        if (!$loadedRelationType) {
            return;
        }

        $query = $this->connection->createQueryBuilder();
        // If relation type matches then delete
        if (((int)$loadedRelationType) === ((int)$type)) {
            $query
                ->delete(self::CONTENT_RELATION_TABLE)
                ->where('id = :relation_id')
                ->setParameter('relation_id', $relationId, ParameterType::INTEGER)
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
                        ':relation_type'
                    )
                )
                // set the relation type as needed for the above & expression
                ->setParameter('relation_type', ~$type, ParameterType::INTEGER)
                ->where('id = :relation_id')
                ->setParameter('relation_id', $relationId, ParameterType::INTEGER)
            ;

            $query->executeStatement();
        }
    }

    /**
     * @return int[]
     */
    public function getContentIdsByContentTypeId(int $contentTypeId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('id')
            ->from(self::CONTENT_ITEM_TABLE)
            ->where('content_type_id = :content_type_id')
            ->setParameter('content_type_id', $contentTypeId, ParameterType::INTEGER);

        $statement = $query->executeQuery();

        return array_map('intval', $statement->fetchAllAssociative());
    }

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
                'l.content_type_field_definition_id',
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
        $contentLinkTable = Gateway::CONTENT_RELATION_TABLE;
        $insertQuery = <<<SQL
            INSERT INTO $contentLinkTable (
                content_type_field_definition_id,
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
     * {@inheritdoc}
     *
     * @throws \Doctrine\DBAL\ConnectionException
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
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function deleteTranslatedFields(
        string $languageCode,
        int $contentId,
        ?int $versionNo = null
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(Gateway::CONTENT_FIELD_TABLE)
            ->where('contentobject_id = :contentId')
            ->andWhere('language_code = :languageCode')
            ->setParameters(
                [
                    'contentId' => $contentId,
                    'languageCode' => $languageCode,
                ]
            )
        ;

        if (null !== $versionNo) {
            $query
                ->andWhere('version = :versionNo')
                ->setParameter('versionNo', $versionNo)
            ;
        }

        $query->executeStatement();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\DBAL\Exception
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
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Delete translation from the ibexa_content_name table.
     *
     * @param int $versionNo optional, if specified, apply to this Version only.
     */
    private function deleteTranslationFromContentNames(
        int $contentId,
        string $languageCode,
        ?int $versionNo = null
    ) {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(Gateway::CONTENT_NAME_TABLE)
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
                ->andWhere('content_version = :versionNo')
                ->setParameter('versionNo', $versionNo)
            ;
        }

        $query->executeStatement();
    }

    /**
     * Remove language from ibexa_content_translation.
     *
     * @param int $contentId
     * @param int $languageId
     *
     * @throws \Ibexa\Core\Base\Exceptions\BadStateException
     */
    private function deleteTranslationFromContentObject($contentId, $languageId)
    {
        $hasOtherLanguages = (bool)$this->connection->fetchOne(
            'SELECT 1 FROM ibexa_content_translation WHERE content_id = :contentId AND language_id != :languageId',
            ['contentId' => $contentId, 'languageId' => $languageId],
            ['contentId' => ParameterType::INTEGER, 'languageId' => ParameterType::INTEGER]
        );

        // most likely somehow it was the last remaining translation
        if (!$hasOtherLanguages) {
            throw new BadStateException(
                '$languageCode',
                'The provided translation is the only translation in this version'
            );
        }

        $query = $this->connection->createQueryBuilder();
        $query->update(Gateway::CONTENT_ITEM_TABLE)
            ->set('modified', ':now')
            ->where('id = :contentId')
            ->setParameter('now', time())
            ->setParameter('contentId', $contentId)
        ;
        $query->executeStatement();

        $this->connection->executeStatement(
            'DELETE FROM ibexa_content_translation WHERE content_id = :contentId AND language_id = :languageId',
            ['contentId' => $contentId, 'languageId' => $languageId],
            ['contentId' => ParameterType::INTEGER, 'languageId' => ParameterType::INTEGER]
        );
    }

    /**
     * Remove language from ibexa_content_version_translation and update initialLanguageId
     * if it matches the removed one.
     *
     * @param int|null $versionNo optional, if specified, apply to this Version only.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    private function deleteTranslationFromContentVersions(
        int $contentId,
        int $languageId,
        ?int $versionNo = null
    ) {
        $contentTable = Gateway::CONTENT_ITEM_TABLE;
        $versionTable = Gateway::CONTENT_VERSION_TABLE;
        $query = $this->connection->createQueryBuilder();
        $query->update($versionTable)
            ->set('modified', ':now')
            // update initial_language_id only if it matches removed translation languageId
            ->set(
                'initial_language_id',
                'CASE WHEN initial_language_id = :languageId ' .
                "THEN (SELECT initial_language_id AS main_language_id FROM $contentTable c WHERE c.id = :contentId) " .
                'ELSE initial_language_id END'
            )
            ->where('contentobject_id = :contentId')
            ->andWhere(
                // make sure removed translation is not the last one (incl. alwaysAvailable)
                "EXISTS (SELECT 1 FROM ibexa_content_version_translation cvt WHERE cvt.content_version_id = {$versionTable}.id AND cvt.language_id != :languageId)"
            )
            ->setParameter('now', time())
            ->setParameter('contentId', $contentId)
            ->setParameter('languageId', $languageId)
        ;

        if (null !== $versionNo) {
            $query
                ->andWhere('version = :versionNo')
                ->setParameter('versionNo', $versionNo)
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

        $deleteQuery = 'DELETE FROM ibexa_content_version_translation
             WHERE language_id = :languageId
             AND content_version_id IN (
                 SELECT id FROM ibexa_content_version WHERE contentobject_id = :contentId'
            . (null !== $versionNo ? ' AND version = :versionNo' : '') . ')';

        $deleteParams = ['contentId' => $contentId, 'languageId' => $languageId];
        $deleteTypes = ['contentId' => ParameterType::INTEGER, 'languageId' => ParameterType::INTEGER];
        if (null !== $versionNo) {
            $deleteParams['versionNo'] = $versionNo;
            $deleteTypes['versionNo'] = ParameterType::INTEGER;
        }

        $this->connection->executeStatement($deleteQuery, $deleteParams, $deleteTypes);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
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

    private function getDatabasePlatform(): AbstractPlatform
    {
        try {
            return $this->connection->getDatabasePlatform();
        } catch (Exception $e) {
            throw DatabaseException::wrap($e);
        }
    }
}
