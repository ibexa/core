<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content;

use Ibexa\Contracts\Core\Event\Mapper\ResolveMissingFieldEvent;
use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Persistence\Content\Relation;
use Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct as RelationCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as ContentTypeHandler;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\FieldType\FieldTypeAliasResolverInterface;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry as Registry;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Mapper for Content Handler.
 *
 * Performs mapping of Content objects.
 *
 * @phpstan-type TVersionedLanguageFieldDefinitionsMap array<
 *     int, array<
 *         int, array<
 *             string, array<
 *                 int, \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition,
 *             >
 *         >
 *     >
 * >
 * @phpstan-type TVersionedFieldMap array<
 *     int, array<
 *         int, array<
 *             int, \Ibexa\Contracts\Core\Persistence\Content\Field,
 *         >
 *     >
 * >
 * @phpstan-type TVersionedNameMap array<
 *     int, array<
 *         int, array<string, string>
 *     >
 * >
 * @phpstan-type TContentInfoMap array<int, \Ibexa\Contracts\Core\Persistence\Content\ContentInfo>
 * @phpstan-type TVersionInfoMap array<
 *     int, array<
 *         int, \Ibexa\Contracts\Core\Persistence\Content\VersionInfo,
 *     >
 * >
 * @phpstan-type TRawContentRow array<string, scalar>
 */
class Mapper
{
    protected Registry $converterRegistry;

    protected LanguageHandler $languageHandler;

    private ContentTypeHandler $contentTypeHandler;

    private EventDispatcherInterface $eventDispatcher;

    private FieldTypeAliasResolverInterface $fieldTypeAliasResolver;

    public function __construct(
        Registry $converterRegistry,
        LanguageHandler $languageHandler,
        ContentTypeHandler $contentTypeHandler,
        EventDispatcherInterface $eventDispatcher,
        FieldTypeAliasResolverInterface $fieldTypeAliasResolver
    ) {
        $this->converterRegistry = $converterRegistry;
        $this->languageHandler = $languageHandler;
        $this->contentTypeHandler = $contentTypeHandler;
        $this->eventDispatcher = $eventDispatcher;
        $this->fieldTypeAliasResolver = $fieldTypeAliasResolver;
    }

    /**
     * Creates a Content from the given $struct and $currentVersionNo.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\CreateStruct $struct
     * @param mixed $currentVersionNo
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ContentInfo
     */
    private function createContentInfoFromCreateStruct(CreateStruct $struct, $currentVersionNo = 1)
    {
        $contentInfo = new ContentInfo();

        $contentInfo->id = null;
        $contentInfo->contentTypeId = $struct->typeId;
        $contentInfo->sectionId = $struct->sectionId;
        $contentInfo->ownerId = $struct->ownerId;
        $contentInfo->alwaysAvailable = $struct->alwaysAvailable;
        $contentInfo->remoteId = $struct->remoteId;
        $contentInfo->mainLanguageCode = $this->languageHandler
            ->load($struct->mainLanguageId ?? $struct->initialLanguageId)
            ->languageCode;
        $contentInfo->name = $struct->name[$contentInfo->mainLanguageCode] ?? '';
        // For drafts published and modified timestamps should be 0
        $contentInfo->publicationDate = 0;
        $contentInfo->modificationDate = 0;
        $contentInfo->currentVersionNo = $currentVersionNo;
        $contentInfo->status = ContentInfo::STATUS_DRAFT;
        $contentInfo->isHidden = $struct->isHidden ?? false;

        return $contentInfo;
    }

    /**
     * Creates a new version for the given $struct and $versionNo.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\CreateStruct $struct
     * @param mixed $versionNo
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\VersionInfo
     */
    public function createVersionInfoFromCreateStruct(CreateStruct $struct, $versionNo)
    {
        $versionInfo = new VersionInfo();

        $versionInfo->id = null;
        $versionInfo->contentInfo = $this->createContentInfoFromCreateStruct($struct, $versionNo);
        $versionInfo->versionNo = $versionNo;
        $versionInfo->creatorId = $struct->ownerId;
        $versionInfo->status = VersionInfo::STATUS_DRAFT;
        $versionInfo->initialLanguageCode = $this->languageHandler->load($struct->initialLanguageId)->languageCode;
        $versionInfo->creationDate = $struct->modified;
        $versionInfo->modificationDate = $struct->modified;
        $versionInfo->names = $struct->name;

        $languages = [];
        foreach ($struct->fields as $field) {
            if (!isset($languages[$field->languageCode])) {
                $languages[$field->languageCode] = true;
            }
        }
        $versionInfo->languageCodes = array_keys($languages);

        return $versionInfo;
    }

    /**
     * Creates a new version for the given $content.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content $content
     * @param mixed $versionNo
     * @param mixed $userId
     * @param string|null $languageCode
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\VersionInfo
     */
    public function createVersionInfoForContent(Content $content, $versionNo, $userId, ?string $languageCode = null)
    {
        $versionInfo = new VersionInfo();

        $versionInfo->contentInfo = $content->versionInfo->contentInfo;
        $versionInfo->versionNo = $versionNo;
        $versionInfo->creatorId = $userId;
        $versionInfo->status = VersionInfo::STATUS_DRAFT;
        $versionInfo->initialLanguageCode = $languageCode ?? $content->versionInfo->initialLanguageCode;
        $versionInfo->creationDate = time();
        $versionInfo->modificationDate = $versionInfo->creationDate;
        $versionInfo->names = is_object($content->versionInfo) ? $content->versionInfo->names : [];
        $versionInfo->languageCodes = $content->versionInfo->languageCodes;

        return $versionInfo;
    }

    /**
     * Converts value of $field to storage value.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue
     */
    public function convertToStorageValue(Field $field)
    {
        $converter = $this->converterRegistry->getConverter(
            $field->type
        );
        $storageValue = new StorageFieldValue();
        $converter->toStorageValue(
            $field->value,
            $storageValue
        );

        return $storageValue;
    }

    /**
     * Extracts Content objects (and nested) from database result $rows.
     *
     * Expects database rows to be indexed by keys of the format
     *
     *      "$tableName_$columnName"
     *
     * @param array<array<string, scalar>> $rows
     * @param array<array<string, scalar>> $nameRows
     * @param string $prefix
     * @param array<string>|null $translations
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content[]
     */
    public function extractContentFromRows(
        array $rows,
        array $nameRows,
        string $prefix = 'content_',
        ?array $translations = null
    ): array {
        $versionedNameData = [];

        foreach ($nameRows as $row) {
            $contentId = (int)$row["{$prefix}name_contentobject_id"];
            $versionNo = (int)$row["{$prefix}name_content_version"];
            $languageCode = (string)$row["{$prefix}name_content_translation"];
            $versionedNameData[$contentId][$versionNo][$languageCode] = (string)$row["{$prefix}name_name"];
        }

        $contentInfos = [];
        $versionInfos = [];
        $fields = [];

        $fieldDefinitions = $this->loadCachedVersionFieldDefinitionsPerLanguage(
            $rows,
            $prefix,
            $translations
        );

        foreach ($rows as $row) {
            $contentId = (int)$row["{$prefix}id"];
            $versionId = (int)$row["{$prefix}version_id"];

            if (!isset($contentInfos[$contentId])) {
                $contentInfos[$contentId] = $this->extractContentInfoFromRow($row, $prefix);
            }

            if (!isset($versionInfos[$contentId])) {
                $versionInfos[$contentId] = [];
            }

            if (!isset($versionInfos[$contentId][$versionId])) {
                $versionInfos[$contentId][$versionId] = $this->extractVersionInfoFromRow($row);
            }

            $fieldId = (int)$row["{$prefix}field_id"];
            $fieldDefinitionId = (int)$row["{$prefix}field_content_type_field_definition_id"];
            $languageCode = $row["{$prefix}field_language_code"];

            if (!isset($fields[$contentId][$versionId][$fieldId])
                && isset($fieldDefinitions[$contentId][$versionId][$languageCode][$fieldDefinitionId])
            ) {
                $fields[$contentId][$versionId][$fieldId] = $this->extractFieldFromRow($row);
                unset($fieldDefinitions[$contentId][$versionId][$languageCode][$fieldDefinitionId]);
            }
        }

        return $this->buildContentObjects(
            $contentInfos,
            $versionInfos,
            $fields,
            $fieldDefinitions,
            $versionedNameData
        );
    }

    /**
     * @phpstan-param TContentInfoMap $contentInfos
     * @phpstan-param TVersionInfoMap $versionInfos
     * @phpstan-param TVersionedFieldMap $fields
     * @phpstan-param TVersionedLanguageFieldDefinitionsMap $missingFieldDefinitions
     * @phpstan-param TVersionedNameMap $versionedNames
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content[]
     */
    private function buildContentObjects(
        array $contentInfos,
        array $versionInfos,
        array $fields,
        array $missingFieldDefinitions,
        array $versionedNames
    ): array {
        $results = [];

        foreach ($contentInfos as $contentId => $contentInfo) {
            foreach ($versionInfos[$contentId] as $versionId => $versionInfo) {
                // Fallback to just main language name if versioned name data is missing
                $names = $versionedNames[$contentId][$versionInfo->versionNo]
                    ?? [$contentInfo->mainLanguageCode => $contentInfo->name];

                $content = new Content();
                $content->versionInfo = $versionInfo;
                $content->versionInfo->names = $names;
                $content->versionInfo->contentInfo = $contentInfo;
                $content->fields = array_values($fields[$contentId][$versionId] ?? []);

                $missingVersionFieldDefinitions = $missingFieldDefinitions[$contentId][$versionId] ?? [];

                foreach ($missingVersionFieldDefinitions as $languageCode => $versionFieldDefinitions) {
                    foreach ($versionFieldDefinitions as $fieldDefinition) {
                        $event = $this->eventDispatcher->dispatch(
                            new ResolveMissingFieldEvent(
                                $content,
                                $fieldDefinition,
                                $languageCode
                            )
                        );

                        $field = $event->getField();
                        if ($field !== null) {
                            $content->fields[] = $field;
                        }
                    }
                }

                $results[] = $content;
            }
        }

        return $results;
    }

    /**
     * @phpstan-param TRawContentRow[] $rows
     *
     * @param string[]|null $translations
     *
     * @phpstan-return TVersionedLanguageFieldDefinitionsMap
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function loadCachedVersionFieldDefinitionsPerLanguage(
        array $rows,
        string $prefix,
        ?array $translations = null
    ): array {
        $fieldDefinitions = [];
        $contentTypes = [];
        $allLanguages = $this->loadAllLanguagesWithIdKey();

        foreach ($rows as $row) {
            $contentId = (int)$row["{$prefix}id"];
            $versionId = (int)$row["{$prefix}version_id"];
            $contentTypeId = (int)$row["{$prefix}content_type_id"];
            $languageMask = (int)$row["{$prefix}version_language_mask"];

            if (isset($fieldDefinitions[$contentId][$versionId])) {
                continue;
            }

            $allLanguagesCodes = $this->extractLanguageCodesFromMask($languageMask, $allLanguages);
            $languageCodes = empty($translations) ? $allLanguagesCodes : array_intersect($translations, $allLanguagesCodes);
            $contentTypes[$contentTypeId] = $contentTypes[$contentTypeId] ?? $this->contentTypeHandler->load($contentTypeId);
            $contentType = $contentTypes[$contentTypeId];
            foreach ($contentType->fieldDefinitions as $fieldDefinition) {
                foreach ($languageCodes as $languageCode) {
                    $id = (int)$fieldDefinition->id;
                    $languageCode = (string)$languageCode;
                    $fieldDefinitions[$contentId][$versionId][$languageCode][$id] = $fieldDefinition;
                }
            }
        }

        return $fieldDefinitions;
    }

    /**
     * Extracts a ContentInfo object from $row.
     *
     * @phpstan-param TRawContentRow $row
     *
     * @param string $prefix Prefix for row keys, which are initially mapped by ibexa_content fields
     * @param string $treePrefix Prefix for tree row key, which are initially mapped by ibexa_content_tree_ fields
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ContentInfo
     */
    public function extractContentInfoFromRow(array $row, $prefix = '', $treePrefix = 'content_tree_')
    {
        $contentInfo = new ContentInfo();
        $contentInfo->id = (int)$row["{$prefix}id"];
        $contentInfo->name = (string)$row["{$prefix}name"];
        $contentInfo->contentTypeId = (int)$row["{$prefix}content_type_id"];
        $contentInfo->sectionId = (int)$row["{$prefix}section_id"];
        $contentInfo->currentVersionNo = (int)$row["{$prefix}current_version"];
        $contentInfo->ownerId = (int)$row["{$prefix}owner_id"];
        $contentInfo->publicationDate = (int)$row["{$prefix}published"];
        $contentInfo->modificationDate = (int)$row["{$prefix}modified"];
        $contentInfo->alwaysAvailable = 1 === ((int)$row["{$prefix}language_mask"] & 1);
        $contentInfo->mainLanguageCode = $this->languageHandler->load($row["{$prefix}initial_language_id"])->languageCode;
        $contentInfo->remoteId = (string)$row["{$prefix}remote_id"];
        $contentInfo->mainLocationId = ($row["{$treePrefix}main_node_id"] !== null ? (int)$row["{$treePrefix}main_node_id"] : null);
        $contentInfo->status = (int)$row["{$prefix}status"];
        $contentInfo->isHidden = (bool)$row["{$prefix}is_hidden"];

        return $contentInfo;
    }

    /**
     * Extracts ContentInfo objects from $rows.
     *
     * @param array $rows
     * @param string $prefix Prefix for row keys, which are initially mapped by ibexa_content fields
     * @param string $treePrefix Prefix for tree row key, which are initially mapped by ibexa_content_tree_ fields
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ContentInfo[]
     */
    public function extractContentInfoFromRows(array $rows, $prefix = '', $treePrefix = 'content_tree_')
    {
        $contentInfoObjects = [];
        foreach ($rows as $row) {
            $contentInfoObjects[] = $this->extractContentInfoFromRow($row, $prefix, $treePrefix);
        }

        return $contentInfoObjects;
    }

    /**
     * Extracts a VersionInfo object from $row.
     *
     * This method will return VersionInfo with incomplete data. It is intended to be used only by
     * {@link self::extractContentFromRows} where missing data will be filled in.
     *
     * @param array $row
     * @param array $names
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\VersionInfo
     */
    private function extractVersionInfoFromRow(array $row, array $names = [])
    {
        $versionInfo = new VersionInfo();
        $versionInfo->id = (int)$row['content_version_id'];
        $versionInfo->contentInfo = null;
        $versionInfo->versionNo = (int)$row['content_version_version'];
        $versionInfo->creatorId = (int)$row['content_version_creator_id'];
        $versionInfo->creationDate = (int)$row['content_version_created'];
        $versionInfo->modificationDate = (int)$row['content_version_modified'];
        $versionInfo->status = (int)$row['content_version_status'];
        $versionInfo->names = $names;

        // Map language codes
        $allLanguages = $this->loadAllLanguagesWithIdKey();
        $versionInfo->languageCodes = $this->extractLanguageCodesFromMask(
            (int)$row['content_version_language_mask'],
            $allLanguages,
            $missing
        );
        $initialLanguageId = (int)$row['content_version_initial_language_id'];
        if (isset($allLanguages[$initialLanguageId])) {
            $versionInfo->initialLanguageCode = $allLanguages[$initialLanguageId]->languageCode;
        } else {
            $missing[] = $initialLanguageId;
        }

        if (!empty($missing)) {
            throw new NotFoundException(
                'Language',
                implode(', ', $missing) . "' when building content '" . $row['content_id']
            );
        }

        return $versionInfo;
    }

    /**
     * Extracts a VersionInfo object from $row.
     *
     * @phpstan-param TRawContentRow[] $rows
     * @phpstan-param TRawContentRow[] $nameRows
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\VersionInfo[]
     */
    public function extractVersionInfoListFromRows(array $rows, array $nameRows): array
    {
        $nameData = [];
        foreach ($nameRows as $row) {
            $versionId = $row['content_name_contentobject_id'] . '_' . $row['content_name_content_version'];
            $nameData[$versionId][$row['content_name_content_translation']] = $row['content_name_name'];
        }

        $allLanguages = $this->loadAllLanguagesWithIdKey();
        $versionInfoList = [];
        foreach ($rows as $row) {
            $versionId = $row['content_id'] . '_' . $row['content_version_version'];
            if (!isset($versionInfoList[$versionId])) {
                $versionInfo = new VersionInfo();
                $versionInfo->id = (int)$row['content_version_id'];
                $versionInfo->contentInfo = $this->extractContentInfoFromRow($row, 'content_');
                $versionInfo->versionNo = (int)$row['content_version_version'];
                $versionInfo->creatorId = (int)$row['content_version_creator_id'];
                $versionInfo->creationDate = (int)$row['content_version_created'];
                $versionInfo->modificationDate = (int)$row['content_version_modified'];
                $versionInfo->status = (int)$row['content_version_status'];
                $versionInfo->names = $nameData[$versionId];
                $versionInfoList[$versionId] = $versionInfo;
                $versionInfo->languageCodes = $this->extractLanguageCodesFromMask(
                    (int)$row['content_version_language_mask'],
                    $allLanguages,
                    $missing
                );
                $initialLanguageId = (int)$row['content_version_initial_language_id'];
                if (isset($allLanguages[$initialLanguageId])) {
                    $versionInfo->initialLanguageCode = $allLanguages[$initialLanguageId]->languageCode;
                } else {
                    $missing[] = $initialLanguageId;
                }

                if (!empty($missing)) {
                    throw new NotFoundException(
                        'Language',
                        implode(', ', $missing) . "' when building content '" . $row['content_id']
                    );
                }
            }
        }

        return array_values($versionInfoList);
    }

    /**
     * @param int $languageMask
     * @param \Ibexa\Contracts\Core\Persistence\Content\Language[] $allLanguages
     * @param int[] &$missing
     *
     * @return string[]
     */
    private function extractLanguageCodesFromMask(int $languageMask, array $allLanguages, &$missing = [])
    {
        $exp = 2;
        $result = [];

        // Decomposition of $languageMask into its binary components to extract language codes
        // check if $exp has not overflown and became float (happens for the last possible language in the mask)
        while (is_int($exp) && $exp <= $languageMask) {
            if ($languageMask & $exp) {
                if (isset($allLanguages[$exp])) {
                    $result[] = $allLanguages[$exp]->languageCode;
                } else {
                    $missing[] = $exp;
                }
            }

            $exp *= 2;
        }

        return $result;
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\Language[]
     */
    private function loadAllLanguagesWithIdKey()
    {
        $languagesById = [];
        foreach ($this->languageHandler->loadAll() as $language) {
            $languagesById[$language->id] = $language;
        }

        return $languagesById;
    }

    /**
     * Extracts a Field from $row.
     *
     * @param array $row
     */
    protected function extractFieldFromRow(array $row): Field
    {
        $field = new Field();

        $field->id = (int)$row['content_field_id'];
        $field->fieldDefinitionId = (int)$row['content_field_content_type_field_definition_id'];

        $fieldTypeString = $row['content_field_data_type_string'];
        $fieldTypeString = $this->fieldTypeAliasResolver->resolveIdentifier($fieldTypeString);

        $field->type = $fieldTypeString;
        $field->value = $this->extractFieldValueFromRow($row, $field->type);
        $field->languageCode = $row['content_field_language_code'];
        $field->versionNo = isset($row['content_version_version']) ?
            (int)$row['content_version_version'] :
            (int)$row['content_field_version'];

        return $field;
    }

    /**
     * Extracts a FieldValue of $type from $row.
     *
     * @param array $row
     * @param string $type
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\FieldValue
     *
     * @throws \Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter\Exception\NotFound
     *         if the necessary converter for $type could not be found.
     */
    protected function extractFieldValueFromRow(array $row, $type)
    {
        $storageValue = new StorageFieldValue();

        // Nullable field
        $storageValue->dataFloat = isset($row['content_field_data_float'])
            ? (float)$row['content_field_data_float']
            : null;
        // Nullable field
        $storageValue->dataInt = isset($row['content_field_data_int'])
            ? (int)$row['content_field_data_int']
            : null;
        $storageValue->dataText = $row['content_field_data_text'];
        // Not nullable field
        $storageValue->sortKeyInt = (int)$row['content_field_sort_key_int'];
        $storageValue->sortKeyString = $row['content_field_sort_key_string'];

        $fieldValue = new FieldValue();

        $converter = $this->converterRegistry->getConverter($type);
        $converter->toFieldValue($storageValue, $fieldValue);

        return $fieldValue;
    }

    /**
     * Creates CreateStruct from $content.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content $content
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\CreateStruct
     */
    public function createCreateStructFromContent(Content $content)
    {
        $struct = new CreateStruct();
        $struct->name = $content->versionInfo->names;
        $struct->typeId = $content->versionInfo->contentInfo->contentTypeId;
        $struct->sectionId = $content->versionInfo->contentInfo->sectionId;
        $struct->ownerId = $content->versionInfo->contentInfo->ownerId;
        $struct->locations = [];
        $struct->alwaysAvailable = $content->versionInfo->contentInfo->alwaysAvailable;
        $struct->remoteId = md5(uniqid(static::class, true));
        $struct->initialLanguageId = $this->languageHandler->loadByLanguageCode($content->versionInfo->initialLanguageCode)->id;
        $struct->mainLanguageId = $this->languageHandler->loadByLanguageCode($content->versionInfo->contentInfo->mainLanguageCode)->id;
        $struct->modified = time();
        $struct->isHidden = $content->versionInfo->contentInfo->isHidden;

        foreach ($content->fields as $field) {
            $newField = clone $field;
            $newField->id = null;
            $struct->fields[] = $newField;
        }

        return $struct;
    }

    /**
     * Extracts relation objects from $rows.
     */
    public function extractRelationsFromRows(array $rows)
    {
        $relations = [];

        foreach ($rows as $row) {
            $id = (int)$row['content_link_id'];
            if (!isset($relations[$id])) {
                $relations[$id] = $this->extractRelationFromRow($row);
            }
        }

        return $relations;
    }

    /**
     * Extracts a Relation object from a $row.
     *
     * @param array $row Associative array representing a relation
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Relation
     */
    public function extractRelationFromRow(array $row)
    {
        $relation = new Relation();
        $relation->id = (int)$row['content_link_id'];
        $relation->sourceContentId = (int)$row['content_link_from_contentobject_id'];
        $relation->sourceContentVersionNo = (int)$row['content_link_from_contentobject_version'];
        $relation->destinationContentId = (int)$row['content_link_to_contentobject_id'];
        $relation->type = (int)$row['content_link_relation_type'];

        $contentClassAttributeId = (int)$row['content_link_content_type_field_definition_id'];
        if ($contentClassAttributeId > 0) {
            $relation->sourceFieldDefinitionId = $contentClassAttributeId;
        }

        return $relation;
    }

    /**
     * Creates a Content from the given $struct.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Relation\CreateStruct $struct
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Relation
     */
    public function createRelationFromCreateStruct(RelationCreateStruct $struct)
    {
        $relation = new Relation();

        $relation->destinationContentId = $struct->destinationContentId;
        $relation->sourceContentId = $struct->sourceContentId;
        $relation->sourceContentVersionNo = $struct->sourceContentVersionNo;
        $relation->sourceFieldDefinitionId = $struct->sourceFieldDefinitionId;
        $relation->type = $struct->type;

        return $relation;
    }
}
