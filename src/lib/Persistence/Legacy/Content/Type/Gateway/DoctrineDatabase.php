<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group\UpdateStruct as GroupUpdateStruct;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Language\MaskGenerator;
use Ibexa\Core\Persistence\Legacy\Content\MultilingualStorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway;
use Ibexa\Core\Persistence\Legacy\SharedGateway\Gateway as SharedGateway;
use function sprintf;

/**
 * Content type gateway implementation using the Doctrine database.
 *
 * @internal Gateway implementation is considered internal. Use Persistence content type Handler instead.
 *
 * @see \Ibexa\Contracts\Core\Persistence\Content\Type\Handler
 */
final class DoctrineDatabase extends Gateway
{
    private array $columns = [
        Gateway::CONTENT_TYPE_TABLE => [
            'id',
            'always_available',
            'contentobject_name',
            'created',
            'creator_id',
            'modified',
            'modifier_id',
            'identifier',
            'initial_language_id',
            'is_container',
            'language_mask',
            'remote_id',
            'serialized_description_list',
            'serialized_name_list',
            'sort_field',
            'sort_order',
            'url_alias_name',
            'status',
        ],
        Gateway::FIELD_DEFINITION_TABLE => [
            'id',
            'can_translate',
            'category',
            'content_type_id',
            'data_float1',
            'data_float2',
            'data_float3',
            'data_float4',
            'data_int1',
            'data_int2',
            'data_int3',
            'data_int4',
            'data_text1',
            'data_text2',
            'data_text3',
            'data_text4',
            'data_text5',
            'data_type_string',
            'identifier',
            'is_information_collector',
            'is_required',
            'is_searchable',
            'is_thumbnail',
            'placement',
            'serialized_data_text',
            'serialized_description_list',
            'serialized_name_list',
        ],
    ];

    public function __construct(
        private readonly Connection $connection,
        private readonly SharedGateway $sharedGateway,
        private readonly MaskGenerator $languageMaskGenerator
    ) {
    }

    public function insertGroup(Group $group): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::CONTENT_TYPE_GROUP_TABLE)
            ->values(
                [
                    'created' => $query->createPositionalParameter(
                        $group->created,
                        ParameterType::INTEGER
                    ),
                    'creator_id' => $query->createPositionalParameter(
                        $group->creatorId,
                        ParameterType::INTEGER
                    ),
                    'modified' => $query->createPositionalParameter(
                        $group->modified,
                        ParameterType::INTEGER
                    ),
                    'modifier_id' => $query->createPositionalParameter(
                        $group->modifierId,
                        ParameterType::INTEGER
                    ),
                    'name' => $query->createPositionalParameter(
                        $group->identifier,
                        ParameterType::STRING
                    ),
                    'is_system' => $query->createPositionalParameter(
                        $group->isSystem ?? false,
                        ParameterType::BOOLEAN
                    ),
                ]
            );
        $query->executeStatement();

        return (int)$this->connection->lastInsertId(self::CONTENT_TYPE_GROUP_SEQ);
    }

    public function updateGroup(GroupUpdateStruct $group): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::CONTENT_TYPE_GROUP_TABLE)
            ->set(
                'modified',
                $query->createPositionalParameter($group->modified, ParameterType::INTEGER)
            )
            ->set(
                'modifier_id',
                $query->createPositionalParameter($group->modifierId, ParameterType::INTEGER)
            )
            ->set(
                'name',
                $query->createPositionalParameter($group->identifier, ParameterType::STRING)
            )
            ->set(
                'is_system',
                $query->createPositionalParameter($group->isSystem, ParameterType::BOOLEAN)
            )->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($group->id, ParameterType::INTEGER)
                )
            );

        $query->executeStatement();
    }

    public function countTypesInGroup(int $groupId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(content_type_id)')
            ->from(self::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE)
            ->where(
                $query->expr()->eq(
                    'group_id',
                    $query->createPositionalParameter($groupId, ParameterType::INTEGER)
                )
            );

        return (int)$query->executeQuery()->fetchOne();
    }

    public function countGroupsForType(int $typeId, int $status): int
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('COUNT(group_id)')
            ->from(self::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE)
            ->where(
                $expr->eq(
                    'content_type_id',
                    $query->createPositionalParameter($typeId, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq(
                    'content_type_status',
                    $query->createPositionalParameter($status, ParameterType::INTEGER)
                )
            );

        return (int)$query->executeQuery()->fetchOne();
    }

    public function deleteGroup(int $groupId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query->delete(self::CONTENT_TYPE_GROUP_TABLE)
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($groupId, ParameterType::INTEGER)
                )
            );
        $query->executeStatement();
    }

    /**
     * @param string[] $languages
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if at least one of the used languages does not exist
     */
    private function insertTypeNameData(int $typeId, int $typeStatus, array $languages): void
    {
        $tmpLanguages = $languages;
        if (isset($tmpLanguages['always-available'])) {
            unset($tmpLanguages['always-available']);
        }

        foreach ($tmpLanguages as $language => $name) {
            $query = $this->connection->createQueryBuilder();
            $query
                ->insert(self::CONTENT_TYPE_NAME_TABLE)
                ->values(
                    [
                        'content_type_id' => $query->createPositionalParameter(
                            $typeId,
                            ParameterType::INTEGER
                        ),
                        'content_type_status' => $query->createPositionalParameter(
                            $typeStatus,
                            ParameterType::INTEGER
                        ),
                        'language_id' => $query->createPositionalParameter(
                            $this->languageMaskGenerator->generateLanguageIndicator(
                                $language,
                                $this->languageMaskGenerator->isLanguageAlwaysAvailable(
                                    $language,
                                    $languages
                                )
                            ),
                            ParameterType::INTEGER
                        ),
                        'language_locale' => $query->createPositionalParameter(
                            $language,
                            ParameterType::STRING
                        ),
                        'name' => $query->createPositionalParameter($name, ParameterType::STRING),
                    ]
                );
            $query->executeStatement();
        }
    }

    private function setNextAutoIncrementedValueIfAvailable(
        QueryBuilder $queryBuilder,
        string $tableName,
        string $idColumnName,
        string $sequenceName,
        ?int $defaultValue = null
    ): void {
        if (null === $defaultValue) {
            // usually returns null to trigger default column value behavior
            $defaultValue = $this->sharedGateway->getColumnNextIntegerValue(
                $tableName,
                $idColumnName,
                $sequenceName
            );
        }
        // avoid trying to insert NULL to trigger default column value behavior
        if (null !== $defaultValue) {
            $queryBuilder->setValue(
                $idColumnName,
                $queryBuilder->createNamedParameter(
                    $defaultValue,
                    ParameterType::INTEGER,
                    ":{$idColumnName}"
                )
            );
        }
    }

    public function insertType(Type $type, ?int $typeId = null): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::CONTENT_TYPE_TABLE)
            ->values(
                [
                    'status' => $query->createNamedParameter(
                        $type->status,
                        ParameterType::INTEGER,
                        ':status'
                    ),
                    'created' => $query->createNamedParameter(
                        $type->created,
                        ParameterType::INTEGER,
                        ':created'
                    ),
                    'creator_id' => $query->createNamedParameter(
                        $type->creatorId,
                        ParameterType::INTEGER,
                        ':creator_id'
                    ),
                ]
            );
        $this->setNextAutoIncrementedValueIfAvailable(
            $query,
            self::CONTENT_TYPE_TABLE,
            'id',
            self::CONTENT_TYPE_SEQ,
            $typeId
        );

        $columnQueryValueAndTypeMap = $this->mapCommonContentTypeColumnsToQueryValuesAndTypes(
            $type
        );
        foreach ($columnQueryValueAndTypeMap as $columnName => $data) {
            [$value, $parameterType] = $data;
            $query
                ->setValue(
                    $columnName,
                    $query->createNamedParameter($value, $parameterType, ":{$columnName}")
                );
        }

        $query->setParameter('status', $type->status, ParameterType::INTEGER);
        $query->setParameter('created', $type->created, ParameterType::INTEGER);
        $query->setParameter('creator_id', $type->creatorId, ParameterType::INTEGER);

        $query->executeStatement();

        if (empty($typeId)) {
            $typeId = $this->sharedGateway->getLastInsertedId(self::CONTENT_TYPE_SEQ);
        }

        $this->insertTypeNameData($typeId, $type->status, $type->name);

        // $typeId passed as the argument could still be non-int
        return (int)$typeId;
    }

    /**
     * Get a map of content type storage column name to its value and parameter type.
     *
     * Key value of the map is represented as a two-elements array with column value and its type.
     */
    private function mapCommonContentTypeColumnsToQueryValuesAndTypes(Type $type): array
    {
        return [
            'serialized_name_list' => [serialize($type->name), ParameterType::STRING],
            'serialized_description_list' => [serialize($type->description), ParameterType::STRING],
            'identifier' => [$type->identifier, ParameterType::STRING],
            'modified' => [$type->modified, ParameterType::INTEGER],
            'modifier_id' => [$type->modifierId, ParameterType::INTEGER],
            'remote_id' => [$type->remoteId, ParameterType::STRING],
            'url_alias_name' => [$type->urlAliasSchema, ParameterType::STRING],
            'contentobject_name' => [$type->nameSchema, ParameterType::STRING],
            'is_container' => [(int)$type->isContainer, ParameterType::INTEGER],
            'language_mask' => [
                $this->languageMaskGenerator->generateLanguageMaskFromLanguageCodes(
                    $type->languageCodes,
                    array_key_exists('always-available', $type->name)
                ),
                ParameterType::INTEGER,
            ],
            'initial_language_id' => [$type->initialLanguageId, ParameterType::INTEGER],
            'sort_field' => [$type->sortField, ParameterType::INTEGER],
            'sort_order' => [$type->sortOrder, ParameterType::INTEGER],
            'always_available' => [(int)$type->defaultAlwaysAvailable, ParameterType::INTEGER],
        ];
    }

    public function insertGroupAssignment(int $groupId, int $typeId, int $status): void
    {
        $groups = $this->loadGroupData([$groupId]);
        if (empty($groups)) {
            throw new NotFoundException('Content type group', $groupId);
        }
        $group = $groups[0];

        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE)
            ->values(
                [
                    'content_type_id' => $query->createPositionalParameter(
                        $typeId,
                        ParameterType::INTEGER
                    ),
                    'content_type_status' => $query->createPositionalParameter(
                        $status,
                        ParameterType::INTEGER
                    ),
                    'group_id' => $query->createPositionalParameter(
                        $groupId,
                        ParameterType::INTEGER
                    ),
                    'group_name' => $query->createPositionalParameter(
                        $group['name'],
                        ParameterType::STRING
                    ),
                ]
            );

        $query->executeStatement();
    }

    public function deleteGroupAssignment(int $groupId, int $typeId, int $status): void
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->delete(self::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE)
            ->where(
                $expr->eq(
                    'content_type_id',
                    $query->createPositionalParameter($typeId, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq(
                    'content_type_status',
                    $query->createPositionalParameter($status, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq(
                    'group_id',
                    $query->createPositionalParameter($groupId, ParameterType::INTEGER)
                )
            );
        $query->executeStatement();
    }

    public function loadGroupData(array $groupIds): array
    {
        $query = $this->createGroupLoadQuery();
        $query
            ->where($query->expr()->in('id', ':ids'))
            ->setParameter('ids', $groupIds, Connection::PARAM_INT_ARRAY);

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadGroupDataByIdentifier(string $identifier): array
    {
        $query = $this->createGroupLoadQuery();
        $query->where(
            $query->expr()->eq(
                'name',
                $query->createPositionalParameter($identifier, ParameterType::STRING)
            )
        );

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadAllGroupsData(): array
    {
        $query = $this->createGroupLoadQuery();

        $query->andWhere(
            $query->expr()->eq(
                'is_system',
                $query->createPositionalParameter(false, ParameterType::BOOLEAN)
            )
        );

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * Create the basic query to load Group data.
     */
    private function createGroupLoadQuery(): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(
            'created',
            'creator_id',
            'id',
            'modified',
            'modifier_id',
            'name',
            'is_system'
        )->from(self::CONTENT_TYPE_GROUP_TABLE);

        return $query;
    }

    public function loadTypesDataForGroup(int $groupId, int $status): array
    {
        $query = $this->getLoadTypeQueryBuilder();
        $expr = $query->expr();
        $query
            ->where($expr->eq('g.group_id', ':gid'))
            ->andWhere($expr->eq('c.status', ':status'))
            ->addOrderBy('c.identifier')
            ->setParameter('gid', $groupId, ParameterType::INTEGER)
            ->setParameter('status', $status, ParameterType::INTEGER);

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function insertFieldDefinition(
        int $typeId,
        int $status,
        FieldDefinition $fieldDefinition,
        StorageFieldDefinition $storageFieldDef
    ): int {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::FIELD_DEFINITION_TABLE)
            ->values(
                [
                    'content_type_id' => $query->createNamedParameter(
                        $typeId,
                        ParameterType::INTEGER,
                        ':content_type_id'
                    ),
                    'status' => $query->createNamedParameter(
                        $status,
                        ParameterType::INTEGER,
                        ':status'
                    ),
                ]
            );
        $this->setNextAutoIncrementedValueIfAvailable(
            $query,
            self::FIELD_DEFINITION_TABLE,
            'id',
            self::FIELD_DEFINITION_SEQ,
            $fieldDefinition->id
        );
        $columnValueAndTypeMap = $this->mapCommonFieldDefinitionColumnsToQueryValuesAndTypes(
            $fieldDefinition,
            $storageFieldDef
        );
        foreach ($columnValueAndTypeMap as $columnName => $data) {
            [$columnValue, $parameterType] = $data;
            $query
                ->setValue($columnName, ":{$columnName}")
                ->setParameter($columnName, $columnValue, $parameterType);
        }

        $query->executeStatement();

        $fieldDefinitionId = $fieldDefinition->id ?? $this->sharedGateway->getLastInsertedId(
            self::FIELD_DEFINITION_SEQ
        );

        foreach ($storageFieldDef->multilingualData as $multilingualData) {
            $this->insertFieldDefinitionMultilingualData(
                $fieldDefinitionId,
                $multilingualData,
                $status
            );
        }

        return $fieldDefinitionId;
    }

    private function insertFieldDefinitionMultilingualData(
        int $fieldDefinitionId,
        MultilingualStorageFieldDefinition $multilingualData,
        int $status
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::MULTILINGUAL_FIELD_DEFINITION_TABLE)
            ->values(
                [
                    'data_text' => ':data_text',
                    'data_json' => ':data_json',
                    'name' => ':name',
                    'description' => ':description',
                    'content_type_field_definition_id' => ':field_definition_id',
                    'status' => ':status',
                    'language_id' => ':language_id',
                ]
            )
            ->setParameter('data_text', $multilingualData->dataText)
            ->setParameter('data_json', $multilingualData->dataJson)
            ->setParameter('name', $multilingualData->name)
            ->setParameter('description', $multilingualData->description)
            ->setParameter('field_definition_id', $fieldDefinitionId, ParameterType::INTEGER)
            ->setParameter('status', $status, ParameterType::INTEGER)
            ->setParameter('language_id', $multilingualData->languageId, ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * Get a map of Field Definition storage column name to its value and parameter type.
     *
     * Key value of the map is represented as a two-elements array with column value and its type.
     */
    private function mapCommonFieldDefinitionColumnsToQueryValuesAndTypes(
        FieldDefinition $fieldDefinition,
        StorageFieldDefinition $storageFieldDef
    ): array {
        return [
            'serialized_name_list' => [serialize($fieldDefinition->name), ParameterType::STRING],
            'serialized_description_list' => [
                serialize($fieldDefinition->description),
                ParameterType::STRING,
            ],
            'serialized_data_text' => [
                serialize($storageFieldDef->serializedDataText),
                ParameterType::STRING,
            ],
            'identifier' => [$fieldDefinition->identifier, ParameterType::STRING],
            'category' => [$fieldDefinition->fieldGroup, ParameterType::STRING],
            'placement' => [$fieldDefinition->position, ParameterType::INTEGER],
            'data_type_string' => [$fieldDefinition->fieldType, ParameterType::STRING],
            'can_translate' => [(int)$fieldDefinition->isTranslatable, ParameterType::INTEGER],
            'is_thumbnail' => [(bool)$fieldDefinition->isThumbnail, ParameterType::INTEGER],
            'is_required' => [(int)$fieldDefinition->isRequired, ParameterType::INTEGER],
            'is_information_collector' => [
                (int)$fieldDefinition->isInfoCollector,
                ParameterType::INTEGER,
            ],
            'is_searchable' => [(int)$fieldDefinition->isSearchable, ParameterType::INTEGER],
            'data_float1' => [$storageFieldDef->dataFloat1, null],
            'data_float2' => [$storageFieldDef->dataFloat2, null],
            'data_float3' => [$storageFieldDef->dataFloat3, null],
            'data_float4' => [$storageFieldDef->dataFloat4, null],
            'data_int1' => [$storageFieldDef->dataInt1, ParameterType::INTEGER],
            'data_int2' => [$storageFieldDef->dataInt2, ParameterType::INTEGER],
            'data_int3' => [$storageFieldDef->dataInt3, ParameterType::INTEGER],
            'data_int4' => [$storageFieldDef->dataInt4, ParameterType::INTEGER],
            'data_text1' => [$storageFieldDef->dataText1, ParameterType::STRING],
            'data_text2' => [$storageFieldDef->dataText2, ParameterType::STRING],
            'data_text3' => [$storageFieldDef->dataText3, ParameterType::STRING],
            'data_text4' => [$storageFieldDef->dataText4, ParameterType::STRING],
            'data_text5' => [$storageFieldDef->dataText5, ParameterType::STRING],
        ];
    }

    public function loadFieldDefinition(int $id, int $status): array
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $this
            ->selectColumns($query, self::FIELD_DEFINITION_TABLE, 'f_def')
            ->addSelect(
                'ct.initial_language_id AS content_type_initial_language_id',
                'transl_f_def.name AS content_type_field_definition_multilingual_name',
                'transl_f_def.description AS content_type_field_definition_multilingual_description',
                'transl_f_def.language_id AS content_type_field_definition_multilingual_language_id',
                'transl_f_def.data_text AS content_type_field_definition_multilingual_data_text',
                'transl_f_def.data_json AS content_type_field_definition_multilingual_data_json'
            )
            ->from(self::FIELD_DEFINITION_TABLE, 'f_def')
            ->leftJoin(
                'f_def',
                self::CONTENT_TYPE_TABLE,
                'ct',
                $expr->and(
                    $expr->eq('f_def.content_type_id', 'ct.id'),
                    $expr->eq('f_def.status', 'ct.status')
                )
            )
            ->leftJoin(
                'f_def',
                self::MULTILINGUAL_FIELD_DEFINITION_TABLE,
                'transl_f_def',
                $expr->and(
                    $expr->eq(
                        'f_def.id',
                        'transl_f_def.content_type_field_definition_id'
                    ),
                    $expr->eq(
                        'f_def.status',
                        'transl_f_def.status'
                    )
                )
            )
            ->where(
                $expr->eq(
                    'f_def.id',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq(
                    'f_def.status',
                    $query->createPositionalParameter($status, ParameterType::INTEGER)
                )
            );

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function deleteFieldDefinition(
        int $typeId,
        int $status,
        int $fieldDefinitionId
    ): void {
        // Delete multilingual data first to keep DB integrity
        $deleteQuery = $this->connection->createQueryBuilder();
        $deleteQuery
            ->delete(self::MULTILINGUAL_FIELD_DEFINITION_TABLE)
            ->where('content_type_field_definition_id = :field_definition_id')
            ->andWhere('status = :status')
            ->setParameter('field_definition_id', $fieldDefinitionId, ParameterType::INTEGER)
            ->setParameter('status', $status, ParameterType::INTEGER);

        $deleteQuery->executeStatement();

        // Delete legacy Field Definition data
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::FIELD_DEFINITION_TABLE)
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($fieldDefinitionId, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $query->expr()->eq(
                    'status',
                    $query->createPositionalParameter($status, ParameterType::INTEGER)
                )
            )
        ;

        $query->executeStatement();
    }

    public function updateFieldDefinition(
        int $typeId,
        int $status,
        FieldDefinition $fieldDefinition,
        StorageFieldDefinition $storageFieldDef
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::FIELD_DEFINITION_TABLE)
            ->where('id = :field_definition_id')
            ->andWhere('status = :status')
            ->setParameter('field_definition_id', $fieldDefinition->id, ParameterType::INTEGER)
            ->setParameter('status', $status, ParameterType::INTEGER);

        $fieldDefinitionValueAndTypeMap = $this->mapCommonFieldDefinitionColumnsToQueryValuesAndTypes(
            $fieldDefinition,
            $storageFieldDef
        );
        foreach ($fieldDefinitionValueAndTypeMap as $columnName => $data) {
            [$value, $parameterType] = $data;
            $query
                ->set(
                    $columnName,
                    $query->createNamedParameter($value, $parameterType, ":{$columnName}")
                );
        }

        $query->executeStatement();

        foreach ($storageFieldDef->multilingualData as $data) {
            $dataExists = $this->fieldDefinitionMultilingualDataExist(
                $fieldDefinition,
                $data->languageId,
                $status
            );

            if ($dataExists) {
                $this->updateFieldDefinitionMultilingualData(
                    $fieldDefinition->id,
                    $data,
                    $status
                );
            } else {
                //When creating new translation there are no fields for update.
                $this->insertFieldDefinitionMultilingualData(
                    $fieldDefinition->id,
                    $data,
                    $status
                );
            }
        }
    }

    private function fieldDefinitionMultilingualDataExist(
        FieldDefinition $fieldDefinition,
        int $languageId,
        int $status
    ): bool {
        $existQuery = $this->connection->createQueryBuilder();
        $existQuery
            ->select('COUNT(1)')
            ->from(self::MULTILINGUAL_FIELD_DEFINITION_TABLE)
            ->where('content_type_field_definition_id = :field_definition_id')
            ->andWhere('status = :status')
            ->andWhere('language_id = :language_id')
            ->setParameter('field_definition_id', $fieldDefinition->id, ParameterType::INTEGER)
            ->setParameter('status', $status, ParameterType::INTEGER)
            ->setParameter('language_id', $languageId, ParameterType::INTEGER);

        return 0 < (int)$existQuery->executeQuery()->fetchOne();
    }

    private function updateFieldDefinitionMultilingualData(
        int $fieldDefinitionId,
        MultilingualStorageFieldDefinition $multilingualData,
        int $status
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::MULTILINGUAL_FIELD_DEFINITION_TABLE)
            ->set('data_text', ':data_text')
            ->set('data_json', ':data_json')
            ->set('name', ':name')
            ->set('description', ':description')
            ->where('content_type_field_definition_id = :field_definition_id')
            ->andWhere('status = :status')
            ->andWhere('language_id = :languageId')
            ->setParameter('data_text', $multilingualData->dataText)
            ->setParameter('data_json', $multilingualData->dataJson)
            ->setParameter('name', $multilingualData->name)
            ->setParameter('description', $multilingualData->description)
            ->setParameter('field_definition_id', $fieldDefinitionId, ParameterType::INTEGER)
            ->setParameter('status', $status, ParameterType::INTEGER)
            ->setParameter('languageId', $multilingualData->languageId, ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * Delete entire name data for the given content type of the given status.
     */
    private function deleteTypeNameData(int $typeId, int $typeStatus): void
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->delete(self::CONTENT_TYPE_NAME_TABLE)
            ->where(
                $expr->eq(
                    'content_type_id',
                    $query->createPositionalParameter($typeId, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq(
                    'content_type_status',
                    $query->createPositionalParameter($typeStatus, ParameterType::INTEGER)
                )
            );
        $query->executeStatement();
    }

    public function updateType(int $typeId, int $status, Type $type): void
    {
        $query = $this->connection->createQueryBuilder();
        $query->update(self::CONTENT_TYPE_TABLE);

        $columnQueryValueAndTypeMap = $this->mapCommonContentTypeColumnsToQueryValuesAndTypes(
            $type
        );
        foreach ($columnQueryValueAndTypeMap as $columnName => $data) {
            [$value, $parameterType] = $data;
            $query
                ->set(
                    $columnName,
                    $query->createNamedParameter($value, $parameterType, ":{$columnName}")
                );
        }
        $expr = $query->expr();
        $query
            ->where(
                $expr->eq(
                    'id',
                    $query->createNamedParameter($typeId, ParameterType::INTEGER, ':id')
                )
            )
            ->andWhere(
                $expr->eq(
                    'status',
                    $query->createNamedParameter($status, ParameterType::INTEGER, ':status')
                )
            );

        $query->executeStatement();

        $this->deleteTypeNameData($typeId, $status);
        $this->insertTypeNameData($typeId, $status, $type->name);
    }

    public function loadTypesListData(array $typeIds): array
    {
        $query = $this->getLoadTypeQueryBuilder();

        $query
            ->where($query->expr()->in('c.id', ':ids'))
            ->andWhere($query->expr()->eq('c.status', Type::STATUS_DEFINED))
            ->setParameter('ids', $typeIds, Connection::PARAM_INT_ARRAY);

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadTypesDataByFieldDefinitionIdentifier(string $identifier): array
    {
        $query = $this->getLoadTypeQueryBuilder();
        $query
            ->andWhere(
                $query->expr()->eq(
                    'a.data_type_string',
                    $query->createNamedParameter($identifier)
                )
            );

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadTypeData(int $typeId, int $status): array
    {
        $query = $this->getLoadTypeQueryBuilder();
        $expr = $query->expr();
        $query
            ->where($expr->eq('c.id', ':id'))
            ->andWhere($expr->eq('c.status', ':status'))
            ->setParameter('id', $typeId, ParameterType::INTEGER)
            ->setParameter('status', $status, ParameterType::INTEGER);

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadTypeDataByIdentifier(string $identifier, int $status): array
    {
        $query = $this->getLoadTypeQueryBuilder();
        $expr = $query->expr();
        $query
            ->where($expr->eq('c.identifier', ':identifier'))
            ->andWhere($expr->eq('c.status', ':status'))
            ->setParameter('identifier', $identifier, ParameterType::STRING)
            ->setParameter('status', $status, ParameterType::INTEGER);

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadTypeDataByRemoteId(string $remoteId, int $status): array
    {
        $query = $this->getLoadTypeQueryBuilder();
        $query
            ->where($query->expr()->eq('c.remote_id', ':remote'))
            ->andWhere($query->expr()->eq('c.status', ':status'))
            ->setParameter('remote', $remoteId, ParameterType::STRING)
            ->setParameter('status', $status, ParameterType::INTEGER);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * Return a basic query to retrieve Type data.
     */
    private function getLoadTypeQueryBuilder(): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select(
                'c.id AS content_type_id',
                'c.status AS content_type_status',
                'c.serialized_name_list AS content_type_serialized_name_list',
                'c.serialized_description_list AS content_type_serialized_description_list',
                'c.identifier AS content_type_identifier',
                'c.created AS content_type_created',
                'c.modified AS content_type_modified',
                'c.modifier_id AS content_type_modifier_id',
                'c.creator_id AS content_type_creator_id',
                'c.remote_id AS content_type_remote_id',
                'c.url_alias_name AS content_type_url_alias_name',
                'c.contentobject_name AS content_type_contentobject_name',
                'c.is_container AS content_type_is_container',
                'c.initial_language_id AS content_type_initial_language_id',
                'c.always_available AS content_type_always_available',
                'c.sort_field AS content_type_sort_field',
                'c.sort_order AS content_type_sort_order',
                'c.language_mask AS content_type_language_mask',
                'a.id AS content_type_field_definition_id',
                'a.serialized_name_list AS content_type_field_definition_serialized_name_list',
                'a.serialized_description_list AS content_type_field_definition_serialized_description_list',
                'a.identifier AS content_type_field_definition_identifier',
                'a.category AS content_type_field_definition_category',
                'a.data_type_string AS content_type_field_definition_data_type_string',
                'a.can_translate AS content_type_field_definition_can_translate',
                'a.is_required AS content_type_field_definition_is_required',
                'a.is_information_collector AS content_type_field_definition_is_information_collector',
                'a.is_searchable AS content_type_field_definition_is_searchable',
                'a.is_thumbnail AS content_type_field_definition_is_thumbnail',
                'a.placement AS content_type_field_definition_placement',
                'a.data_float1 AS content_type_field_definition_data_float1',
                'a.data_float2 AS content_type_field_definition_data_float2',
                'a.data_float3 AS content_type_field_definition_data_float3',
                'a.data_float4 AS content_type_field_definition_data_float4',
                'a.data_int1 AS content_type_field_definition_data_int1',
                'a.data_int2 AS content_type_field_definition_data_int2',
                'a.data_int3 AS content_type_field_definition_data_int3',
                'a.data_int4 AS content_type_field_definition_data_int4',
                'a.data_text1 AS content_type_field_definition_data_text1',
                'a.data_text2 AS content_type_field_definition_data_text2',
                'a.data_text3 AS content_type_field_definition_data_text3',
                'a.data_text4 AS content_type_field_definition_data_text4',
                'a.data_text5 AS content_type_field_definition_data_text5',
                'a.serialized_data_text AS content_type_field_definition_serialized_data_text',
                'g.group_id AS content_type_group_assignment_group_id',
                'ml.name AS content_type_field_definition_multilingual_name',
                'ml.description AS content_type_field_definition_multilingual_description',
                'ml.language_id AS content_type_field_definition_multilingual_language_id',
                'ml.data_text AS content_type_field_definition_multilingual_data_text',
                'ml.data_json AS content_type_field_definition_multilingual_data_json'
            )
            ->from(self::CONTENT_TYPE_TABLE, 'c')
            ->leftJoin(
                'c',
                self::FIELD_DEFINITION_TABLE,
                'a',
                $expr->and(
                    $expr->eq('c.id', 'a.content_type_id'),
                    $expr->eq('c.status', 'a.status')
                )
            )
            ->leftJoin(
                'c',
                self::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE,
                'g',
                $expr->and(
                    $expr->eq('c.id', 'g.content_type_id'),
                    $expr->eq('c.status', 'g.content_type_status')
                )
            )
            ->leftJoin(
                'a',
                self::MULTILINGUAL_FIELD_DEFINITION_TABLE,
                'ml',
                $expr->and(
                    $expr->eq('a.id', 'ml.content_type_field_definition_id'),
                    $expr->eq('a.status', 'ml.status')
                )
            )
            ->orderBy('a.placement');

        return $query;
    }

    public function countInstancesOfType(int $typeId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(id)')
            ->from(ContentGateway::CONTENT_ITEM_TABLE)
            ->where(
                $query->expr()->eq(
                    'content_type_id',
                    $query->createPositionalParameter($typeId, ParameterType::INTEGER)
                )
            );

        $stmt = $query->executeQuery();

        return (int)$stmt->fetchOne();
    }

    public function deleteFieldDefinitionsForType(int $typeId, int $status): void
    {
        $ctMlTable = Gateway::MULTILINGUAL_FIELD_DEFINITION_TABLE;
        $subQuery = $this->connection->createQueryBuilder();
        $subQuery
            ->select('f_def.id as content_type_field_definition_id')
            ->from(self::FIELD_DEFINITION_TABLE, 'f_def')
            ->where('f_def.content_type_id = :content_type_id')
            ->andWhere("f_def.id = $ctMlTable.content_type_field_definition_id");

        $deleteQuery = $this->connection->createQueryBuilder();
        $deleteQuery
            ->delete(self::MULTILINGUAL_FIELD_DEFINITION_TABLE)
            ->where(
                sprintf('EXISTS (%s)', $subQuery->getSQL())
            )
            // note: not all drivers support aliasing tables in DELETE query, hence the following:
            ->andWhere(sprintf('%s.status = :status', self::MULTILINGUAL_FIELD_DEFINITION_TABLE))
            ->setParameter('content_type_id', $typeId, ParameterType::INTEGER)
            ->setParameter('status', $status, ParameterType::INTEGER);

        $deleteQuery->executeStatement();

        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->delete(self::FIELD_DEFINITION_TABLE)
            ->where(
                $query->expr()->eq(
                    'content_type_id',
                    $query->createPositionalParameter($typeId, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq(
                    'status',
                    $query->createPositionalParameter($status, ParameterType::INTEGER)
                )
            );

        $query->executeStatement();
    }

    public function delete(int $typeId, int $status): void
    {
        $this->deleteGroupAssignmentsForType($typeId, $status);
        $this->deleteFieldDefinitionsForType($typeId, $status);
        $this->deleteTypeNameData($typeId, $status);
        $this->deleteType($typeId, $status);
    }

    public function deleteType(int $typeId, int $status): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::CONTENT_TYPE_TABLE)
            ->where(
                $query->expr()->and(
                    $query->expr()->eq(
                        'id',
                        $query->createPositionalParameter($typeId, ParameterType::INTEGER)
                    ),
                    $query->expr()->eq(
                        'status',
                        $query->createPositionalParameter($status, ParameterType::INTEGER)
                    )
                )
            );
        $query->executeStatement();
    }

    public function deleteGroupAssignmentsForType(int $typeId, int $status): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE)
            ->where(
                $query->expr()->eq(
                    'content_type_id',
                    $query->createPositionalParameter($typeId, ParameterType::INTEGER)
                )
            )->andWhere(
                $query->expr()->eq(
                    'content_type_status',
                    $query->createPositionalParameter($status, ParameterType::INTEGER)
                )
            );
        $query->executeStatement();
    }

    /**
     * Append all columns of a given table to the SELECT part of a query.
     *
     * Each column is aliased in the form of
     * <code><column_name> AS <table_name>_<column_name></code>.
     */
    private function selectColumns(
        QueryBuilder $queryBuilder,
        string $tableName,
        string $tableAlias = ''
    ): QueryBuilder {
        if (empty($tableAlias)) {
            $tableAlias = $tableName;
        }
        $queryBuilder
            ->addSelect(
                array_map(
                    function (string $columnName) use ($tableName, $tableAlias): string {
                        return sprintf(
                            '%s.%s as %s_%s',
                            $tableAlias,
                            $this->connection->quoteIdentifier($columnName),
                            preg_replace('/^ibexa_/', '', $tableName),
                            $columnName
                        );
                    },
                    $this->columns[$tableName]
                )
            );

        return $queryBuilder;
    }

    public function internalChangeContentTypeStatus(
        int $typeId,
        int $sourceStatus,
        int $targetStatus,
        string $tableName,
        string $typeIdColumnName,
        string $statusColumnName
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update($tableName)
            ->set(
                $statusColumnName,
                $query->createPositionalParameter($targetStatus, ParameterType::INTEGER)
            )
            ->where(
                $query->expr()->eq(
                    $typeIdColumnName,
                    $query->createPositionalParameter($typeId, ParameterType::INTEGER)
                )
            )->andWhere(
                $query->expr()->eq(
                    $statusColumnName,
                    $query->createPositionalParameter($sourceStatus, ParameterType::INTEGER)
                )
            );

        $query->executeStatement();
    }

    public function publishTypeAndFields(int $typeId, int $sourceStatus, int $targetStatus): void
    {
        $this->internalChangeContentTypeStatus(
            $typeId,
            $sourceStatus,
            $targetStatus,
            self::CONTENT_TYPE_TABLE,
            'id',
            'status'
        );

        $this->internalChangeContentTypeStatus(
            $typeId,
            $sourceStatus,
            $targetStatus,
            self::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE,
            'content_type_id',
            'content_type_status'
        );

        $this->internalChangeContentTypeStatus(
            $typeId,
            $sourceStatus,
            $targetStatus,
            self::FIELD_DEFINITION_TABLE,
            'content_type_id',
            'status'
        );

        $this->internalChangeContentTypeStatus(
            $typeId,
            $sourceStatus,
            $targetStatus,
            self::CONTENT_TYPE_NAME_TABLE,
            'content_type_id',
            'content_type_status'
        );
        $ctMlTable = Gateway::MULTILINGUAL_FIELD_DEFINITION_TABLE;
        $subQuery = $this->connection->createQueryBuilder();
        $subQuery
            ->select('f_def.id as content_type_field_definition_id')
            ->from(self::FIELD_DEFINITION_TABLE, 'f_def')
            ->where('f_def.content_type_id = :type_id')
            ->andWhere("f_def.id = $ctMlTable.content_type_field_definition_id");

        $mlDataPublishQuery = $this->connection->createQueryBuilder();
        $mlDataPublishQuery
            ->update(self::MULTILINGUAL_FIELD_DEFINITION_TABLE)
            ->set('status', ':target_status')
            ->where(
                sprintf('EXISTS (%s)', $subQuery->getSQL())
            )
            // note: not all drivers support aliasing tables in UPDATE query, hence the following:
            ->andWhere(
                sprintf('%s.status = :source_status', self::MULTILINGUAL_FIELD_DEFINITION_TABLE)
            )
            ->setParameter('type_id', $typeId, ParameterType::INTEGER)
            ->setParameter('target_status', $targetStatus, ParameterType::INTEGER)
            ->setParameter('source_status', $sourceStatus, ParameterType::INTEGER);

        $mlDataPublishQuery->executeStatement();
    }

    public function getSearchableFieldMapData(): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                'f_def.identifier AS field_definition_identifier',
                'ct.identifier AS content_type_identifier',
                'f_def.id AS field_definition_id',
                'f_def.data_type_string AS field_type_identifier'
            )
            ->from(self::FIELD_DEFINITION_TABLE, 'f_def')
            ->innerJoin('f_def', self::CONTENT_TYPE_TABLE, 'ct', 'f_def.content_type_id = ct.id')
            ->where(
                $query->expr()->eq(
                    'f_def.is_searchable',
                    $query->createPositionalParameter(1, ParameterType::INTEGER)
                )
            );

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function removeFieldDefinitionTranslation(
        int $fieldDefinitionId,
        string $languageCode,
        int $status
    ): void {
        $languageId = $this->languageMaskGenerator->generateLanguageMaskFromLanguageCodes(
            [$languageCode]
        );

        $deleteQuery = $this->connection->createQueryBuilder();
        $deleteQuery
            ->delete(self::MULTILINGUAL_FIELD_DEFINITION_TABLE)
            ->where('content_type_field_definition_id = :field_definition_id')
            ->andWhere('status = :status')
            ->andWhere('language_id = :language_id')
            ->setParameter('field_definition_id', $fieldDefinitionId, ParameterType::INTEGER)
            ->setParameter('status', $status, ParameterType::INTEGER)
            ->setParameter('language_id', $languageId, ParameterType::INTEGER);

        $deleteQuery->executeStatement();
    }

    public function removeByUserAndStatus(int $userId, int $status): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->delete(self::CONTENT_TYPE_TABLE)
            ->where('creator_id = :user or modifier_id = :user')
            ->andWhere('status = :status')
            ->setParameter('user', $userId, ParameterType::INTEGER)
            ->setParameter('status', $status, ParameterType::INTEGER)
        ;

        try {
            $this->connection->beginTransaction();

            $queryBuilder->executeStatement();
            $this->cleanupAssociations();

            $this->connection->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function removeByUserAndVersion(int $userId, int $version): void
    {
        $this->removeByUserAndStatus($userId, $version);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function cleanupAssociations(): void
    {
        $this->cleanupClassAttributeTable();
        $this->cleanupClassAttributeMLTable();
        $this->cleanupClassGroupTable();
        $this->cleanupClassNameTable();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function cleanupClassAttributeTable(): void
    {
        $contentTypeAttrTable = Gateway::FIELD_DEFINITION_TABLE;
        $contentTypeTable = Gateway::CONTENT_TYPE_TABLE;
        $sql = <<<SQL
          DELETE FROM $contentTypeAttrTable 
            WHERE NOT EXISTS (
              SELECT 1 FROM $contentTypeTable
                WHERE $contentTypeTable.id = $contentTypeAttrTable.content_type_id
                AND $contentTypeTable.status = $contentTypeAttrTable.status
            )
SQL;
        $this->connection->executeStatement($sql);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function cleanupClassAttributeMLTable(): void
    {
        $contentTypeAttrMlTable = Gateway::MULTILINGUAL_FIELD_DEFINITION_TABLE;
        $contentTypeAttrTable = Gateway::FIELD_DEFINITION_TABLE;
        $sql = <<<SQL
          DELETE FROM $contentTypeAttrMlTable
            WHERE NOT EXISTS (
              SELECT 1 FROM $contentTypeAttrTable
                WHERE $contentTypeAttrTable.id = $contentTypeAttrMlTable.content_type_field_definition_id
                AND $contentTypeAttrTable.status = $contentTypeAttrMlTable.status
            )
SQL;
        $this->connection->executeStatement($sql);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function cleanupClassGroupTable(): void
    {
        $contentTypeGroupAssignmentTable = Gateway::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE;
        $contentTypeTable = Gateway::CONTENT_TYPE_TABLE;
        $sql = <<<SQL
          DELETE FROM $contentTypeGroupAssignmentTable
            WHERE NOT EXISTS (
              SELECT 1 FROM $contentTypeTable
                WHERE $contentTypeTable.id = $contentTypeGroupAssignmentTable.content_type_id
                AND $contentTypeTable.status = $contentTypeGroupAssignmentTable.content_type_status
            )
SQL;
        $this->connection->executeStatement($sql);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function cleanupClassNameTable(): void
    {
        $contentTypeNameTable = Gateway::CONTENT_TYPE_NAME_TABLE;
        $contentTypeTable = Gateway::CONTENT_TYPE_TABLE;
        $sql = <<< SQL
          DELETE FROM $contentTypeNameTable
            WHERE NOT EXISTS (
              SELECT 1 FROM $contentTypeTable
                WHERE $contentTypeTable.id = $contentTypeNameTable.content_type_id
                AND $contentTypeTable.status = $contentTypeNameTable.content_type_status
            )
SQL;
        $this->connection->executeStatement($sql);
    }
}
