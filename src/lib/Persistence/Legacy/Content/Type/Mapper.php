<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\Type;

use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Contracts\Core\Persistence\Content\Type\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group\CreateStruct as GroupCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Type\UpdateStruct;
use Ibexa\Core\FieldType\FieldTypeAliasResolverInterface;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry;
use Ibexa\Core\Persistence\Legacy\Content\Language\MaskGenerator;
use Ibexa\Core\Persistence\Legacy\Content\MultilingualStorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;

/**
 * Mapper for content type Handler.
 *
 * Performs mapping of Type objects.
 */
class Mapper
{
    /**
     * Converter registry.
     *
     * @var ConverterRegistry
     */
    protected $converterRegistry;

    /** @var MaskGenerator */
    private $maskGenerator;

    private StorageDispatcherInterface $storageDispatcher;

    /**
     * Creates a new content type mapper.
     *
     * @param ConverterRegistry $converterRegistry
     * @param MaskGenerator $maskGenerator
     */
    public function __construct(
        ConverterRegistry $converterRegistry,
        MaskGenerator $maskGenerator,
        StorageDispatcherInterface $storageDispatcher,
        private readonly FieldTypeAliasResolverInterface $fieldTypeAliasResolver
    ) {
        $this->converterRegistry = $converterRegistry;
        $this->maskGenerator = $maskGenerator;
        $this->storageDispatcher = $storageDispatcher;
    }

    /**
     * Creates a Group from its create struct.
     *
     * @param GroupCreateStruct $struct
     *
     * @return Group
     *
     * @todo $description is not supported by database, yet
     */
    public function createGroupFromCreateStruct(GroupCreateStruct $struct)
    {
        $group = new Group();

        $group->name = $struct->name;

        // $group->description is intentionally left out, since DB structure does not support it, yet

        $group->identifier = $struct->identifier;
        $group->created = $struct->created;
        $group->modified = $struct->modified;
        $group->creatorId = $struct->creatorId;
        $group->modifierId = $struct->modifierId;
        $group->isSystem = $struct->isSystem;

        return $group;
    }

    /**
     * Extracts Group objects from the given $rows.
     *
     * @param array $rows
     *
     * @return Group[]
     */
    public function extractGroupsFromRows(array $rows)
    {
        $groups = [];

        foreach ($rows as $row) {
            $group = new Group();
            $group->id = (int)$row['id'];
            $group->created = (int)$row['created'];
            $group->creatorId = (int)$row['creator_id'];
            $group->modified = (int)$row['modified'];
            $group->modifierId = (int)$row['modifier_id'];
            $group->identifier = $row['name'];
            $group->isSystem = (bool)$row['is_system'];

            $groups[] = $group;
        }

        return $groups;
    }

    /**
     * Extracts types and related data from the given $rows.
     *
     * @param array $rows
     * @param bool $keepTypeIdAsKey
     *
     * @return array (Type)
     */
    public function extractTypesFromRows(
        array $rows,
        bool $keepTypeIdAsKey = false
    ) {
        $types = [];
        $fields = [];

        $rowsByAttributeId = [];
        foreach ($rows as $row) {
            $attributeId = (int)$row['content_type_field_definition_id'];
            if (!isset($rowsByAttributeId[$attributeId])) {
                $rowsByAttributeId[$attributeId] = [];
            }

            $rowsByAttributeId[$attributeId][] = $row;
        }

        foreach ($rows as $row) {
            $typeId = (int)$row['content_type_id'];
            if (!isset($types[$typeId])) {
                $types[$typeId] = $this->extractTypeFromRow($row);
            }

            $fieldId = (int)$row['content_type_field_definition_id'];

            if ($fieldId && !isset($fields[$fieldId])) {
                $fieldDataRows = $rowsByAttributeId[$fieldId];

                $multilingualData = $this->extractMultilingualData($fieldDataRows);

                $types[$typeId]->fieldDefinitions[] = $fields[$fieldId] = $this->extractFieldFromRow($row, $multilingualData, $types[$typeId]->status);
            }

            $groupId = (int)$row['content_type_group_assignment_group_id'];
            if (!in_array($groupId, $types[$typeId]->groupIds)) {
                $types[$typeId]->groupIds[] = $groupId;
            }
        }

        foreach ($types as $type) {
            sort($type->groupIds);
        }

        if ($keepTypeIdAsKey) {
            return $types;
        }

        // Re-index $types to avoid people relying on ID keys
        return array_values($types);
    }

    public function extractMultilingualData(array $fieldDefinitionRows): array
    {
        return array_map(static function (array $fieldData) {
            return [
                'content_type_field_definition_multilingual_name' => $fieldData['content_type_field_definition_multilingual_name'] ?? null,
                'content_type_field_definition_multilingual_description' => $fieldData['content_type_field_definition_multilingual_description'] ?? null,
                'content_type_field_definition_multilingual_language_id' => $fieldData['content_type_field_definition_multilingual_language_id'] ?? null,
                'content_type_field_definition_multilingual_data_text' => $fieldData['content_type_field_definition_multilingual_data_text'] ?? null,
                'content_type_field_definition_multilingual_data_json' => $fieldData['content_type_field_definition_multilingual_data_json'] ?? null,
            ];
        }, $fieldDefinitionRows);
    }

    /**
     * Creates a Type from the data in $row.
     *
     * @param array $row
     *
     * @return Type
     */
    protected function extractTypeFromRow(array $row)
    {
        $type = new Type();

        $type->id = (int)$row['content_type_id'];
        $type->status = (int)$row['content_type_status'];
        $type->name = $this->unserialize($row['content_type_serialized_name_list']);
        $type->description = $this->unserialize($row['content_type_serialized_description_list']);
        // Unset redundant data
        unset(
            $type->name['always-available'],
            $type->name[0],
            $type->description['always-available'],
            $type->description[0]
        );
        $type->identifier = $row['content_type_identifier'];
        $type->created = (int)$row['content_type_created'];
        $type->modified = (int)$row['content_type_modified'];
        $type->modifierId = (int)$row['content_type_modifier_id'];
        $type->creatorId = (int)$row['content_type_creator_id'];
        $type->remoteId = $row['content_type_remote_id'];
        $type->urlAliasSchema = $row['content_type_url_alias_name'];
        $type->nameSchema = $row['content_type_contentobject_name'];
        $type->isContainer = ($row['content_type_is_container'] == 1);
        $type->initialLanguageId = (int)$row['content_type_initial_language_id'];
        $type->defaultAlwaysAvailable = ($row['content_type_always_available'] == 1);
        $type->sortField = (int)$row['content_type_sort_field'];
        $type->sortOrder = (int)$row['content_type_sort_order'];
        $type->languageCodes = $this->maskGenerator->extractLanguageCodesFromMask((int)$row['content_type_language_mask']);

        $type->groupIds = [];
        $type->fieldDefinitions = [];

        return $type;
    }

    /**
     * Creates a FieldDefinition from the data in $row.
     *
     * @param array $row
     * @param array $multilingualData
     *
     * @return FieldDefinition
     */
    public function extractFieldFromRow(
        array $row,
        array $multilingualData = [],
        int $status = Type::STATUS_DEFINED
    ) {
        $storageFieldDef = $this->extractStorageFieldFromRow($row, $multilingualData);

        $field = new FieldDefinition();

        $field->id = (int)$row['content_type_field_definition_id'];
        $field->name = $this->unserialize($row['content_type_field_definition_serialized_name_list']);
        $field->description = $this->unserialize($row['content_type_field_definition_serialized_description_list']);
        // Unset redundant data
        unset(
            $field->name['always-available'],
            $field->name[0],
            $field->description['always-available'],
            $field->description[0]
        );

        $dataTypeString = $row['content_type_field_definition_data_type_string'];
        $dataTypeString = $this->fieldTypeAliasResolver->resolveIdentifier($dataTypeString);

        $field->identifier = $row['content_type_field_definition_identifier'];
        $field->fieldGroup = $row['content_type_field_definition_category'];
        $field->fieldType = $dataTypeString;
        $field->isTranslatable = ($row['content_type_field_definition_can_translate'] == 1);
        $field->isRequired = $row['content_type_field_definition_is_required'] == 1;
        $field->isThumbnail = !empty($row['content_type_field_definition_is_thumbnail']);
        $field->isInfoCollector = $row['content_type_field_definition_is_information_collector'] == 1;

        $field->isSearchable = (bool)$row['content_type_field_definition_is_searchable'];
        $field->position = (int)$row['content_type_field_definition_placement'];

        $mainLanguageCode = $this->maskGenerator->extractLanguageCodesFromMask((int)$row['content_type_initial_language_id']);
        $field->mainLanguageCode = array_shift($mainLanguageCode);

        $this->toFieldDefinition($storageFieldDef, $field, $status);

        return $field;
    }

    /**
     * Extracts a StorageFieldDefinition from $row.
     *
     * @param array $row
     * @param array $multilingualDataRow
     *
     * @return StorageFieldDefinition
     */
    protected function extractStorageFieldFromRow(
        array $row,
        array $multilingualDataRow = []
    ) {
        $storageFieldDef = new StorageFieldDefinition();

        $storageFieldDef->dataFloat1 = isset($row['content_type_field_definition_data_float1'])
            ? (float)$row['content_type_field_definition_data_float1']
            : null;
        $storageFieldDef->dataFloat2 = isset($row['content_type_field_definition_data_float2'])
            ? (float)$row['content_type_field_definition_data_float2']
            : null;
        $storageFieldDef->dataFloat3 = isset($row['content_type_field_definition_data_float3'])
            ? (float)$row['content_type_field_definition_data_float3']
            : null;
        $storageFieldDef->dataFloat4 = isset($row['content_type_field_definition_data_float4'])
            ? (float)$row['content_type_field_definition_data_float4']
            : null;
        $storageFieldDef->dataInt1 = isset($row['content_type_field_definition_data_int1'])
            ? (int)$row['content_type_field_definition_data_int1']
            : null;
        $storageFieldDef->dataInt2 = isset($row['content_type_field_definition_data_int2'])
            ? (int)$row['content_type_field_definition_data_int2']
            : null;
        $storageFieldDef->dataInt3 = isset($row['content_type_field_definition_data_int3'])
            ? (int)$row['content_type_field_definition_data_int3']
            : null;
        $storageFieldDef->dataInt4 = isset($row['content_type_field_definition_data_int4'])
            ? (int)$row['content_type_field_definition_data_int4']
            : null;
        $storageFieldDef->dataText1 = $row['content_type_field_definition_data_text1'];
        $storageFieldDef->dataText2 = $row['content_type_field_definition_data_text2'];
        $storageFieldDef->dataText3 = $row['content_type_field_definition_data_text3'];
        $storageFieldDef->dataText4 = $row['content_type_field_definition_data_text4'];
        $storageFieldDef->dataText5 = $row['content_type_field_definition_data_text5'];
        $storageFieldDef->serializedDataText = $row['content_type_field_definition_serialized_data_text'];

        foreach ($multilingualDataRow as $languageDataRow) {
            $languageCodes = $this->maskGenerator->extractLanguageCodesFromMask((int)$languageDataRow['content_type_field_definition_multilingual_language_id']);

            if (empty($languageCodes)) {
                continue;
            }
            $languageCode = reset($languageCodes);

            $multilingualData = new MultilingualStorageFieldDefinition();

            $nameList = $this->unserialize($row['content_type_field_definition_serialized_name_list']);
            $name = $nameList[$languageCode] ?? reset($nameList);
            $description = $this->unserialize($row['content_type_field_definition_serialized_description_list'])[$languageCode] ?? null;

            $multilingualData->name = $languageDataRow['content_type_field_definition_multilingual_name'] ?? $name;
            $multilingualData->description = $languageDataRow['content_type_field_definition_multilingual_description'] ?? $description;
            $multilingualData->dataText = $languageDataRow['content_type_field_definition_multilingual_data_text'];
            $multilingualData->dataJson = $languageDataRow['content_type_field_definition_multilingual_data_json'];
            $multilingualData->languageId = (int)$languageDataRow['content_type_field_definition_multilingual_language_id'];

            $storageFieldDef->multilingualData[$languageCode] = $multilingualData;
        }

        return $storageFieldDef;
    }

    /**
     * Maps properties from $struct to $type.
     *
     * @param CreateStruct $createStruct
     *
     * @return Type
     */
    public function createTypeFromCreateStruct(CreateStruct $createStruct)
    {
        $type = new Type();

        $type->name = $createStruct->name;
        $type->status = $createStruct->status;
        $type->description = $createStruct->description;
        $type->identifier = $createStruct->identifier;
        $type->created = $createStruct->created;
        $type->modified = $createStruct->modified;
        $type->creatorId = $createStruct->creatorId;
        $type->modifierId = $createStruct->modifierId;
        $type->remoteId = $createStruct->remoteId;
        $type->urlAliasSchema = $createStruct->urlAliasSchema;
        $type->nameSchema = $createStruct->nameSchema;
        $type->isContainer = $createStruct->isContainer;
        $type->initialLanguageId = $createStruct->initialLanguageId;
        $type->groupIds = $createStruct->groupIds;
        $type->fieldDefinitions = $createStruct->fieldDefinitions;
        $type->defaultAlwaysAvailable = $createStruct->defaultAlwaysAvailable;
        $type->sortField = $createStruct->sortField;
        $type->sortOrder = $createStruct->sortOrder;
        $type->languageCodes = array_keys($createStruct->name);

        return $type;
    }

    /**
     * Creates a create struct from an existing $type.
     *
     * @param Type $type
     *
     * @return CreateStruct
     */
    public function createCreateStructFromType(Type $type)
    {
        $createStruct = new CreateStruct();

        $createStruct->name = $type->name;
        $createStruct->status = $type->status;
        $createStruct->description = $type->description;
        $createStruct->identifier = $type->identifier;
        $createStruct->created = $type->created;
        $createStruct->modified = $type->modified;
        $createStruct->creatorId = $type->creatorId;
        $createStruct->modifierId = $type->modifierId;
        $createStruct->remoteId = $type->remoteId;
        $createStruct->urlAliasSchema = $type->urlAliasSchema;
        $createStruct->nameSchema = $type->nameSchema;
        $createStruct->isContainer = $type->isContainer;
        $createStruct->initialLanguageId = $type->initialLanguageId;
        $createStruct->groupIds = $type->groupIds;
        $createStruct->fieldDefinitions = $type->fieldDefinitions;
        $createStruct->defaultAlwaysAvailable = $type->defaultAlwaysAvailable;
        $createStruct->sortField = $type->sortField;
        $createStruct->sortOrder = $type->sortOrder;

        return $createStruct;
    }

    /**
     * Creates an update struct from an existing $type.
     *
     * @param Type $type
     *
     * @return UpdateStruct
     */
    public function createUpdateStructFromType(Type $type)
    {
        $updateStruct = new UpdateStruct();

        $updateStruct->name = $type->name;
        $updateStruct->description = $type->description;
        $updateStruct->identifier = $type->identifier;
        $updateStruct->modified = $type->modified;
        $updateStruct->modifierId = $type->modifierId;
        $updateStruct->remoteId = $type->remoteId;
        $updateStruct->urlAliasSchema = $type->urlAliasSchema;
        $updateStruct->nameSchema = $type->nameSchema;
        $updateStruct->isContainer = $type->isContainer;
        $updateStruct->initialLanguageId = $type->initialLanguageId;
        $updateStruct->defaultAlwaysAvailable = $type->defaultAlwaysAvailable;
        $updateStruct->sortField = $type->sortField;
        $updateStruct->sortOrder = $type->sortOrder;

        return $updateStruct;
    }

    /**
     * Maps $fieldDef to the legacy storage specific StorageFieldDefinition.
     *
     * @param FieldDefinition $fieldDef
     * @param StorageFieldDefinition $storageFieldDef
     */
    public function toStorageFieldDefinition(
        FieldDefinition $fieldDef,
        StorageFieldDefinition $storageFieldDef
    ) {
        foreach (array_keys($fieldDef->name) as $languageCode) {
            $multilingualData = new MultilingualStorageFieldDefinition();
            $multilingualData->name = $fieldDef->name[$languageCode];
            $multilingualData->description = $fieldDef->description[$languageCode] ?? null;
            $multilingualData->languageId =
                $this->maskGenerator->generateLanguageMaskFromLanguageCodes([$languageCode]);

            $storageFieldDef->multilingualData[$languageCode] = $multilingualData;
        }

        $converter = $this->converterRegistry->getConverter(
            $fieldDef->fieldType
        );

        $converter->toStorageFieldDefinition(
            $fieldDef,
            $storageFieldDef
        );
    }

    /**
     * Maps a FieldDefinition from the given $storageFieldDef.
     *
     * @param StorageFieldDefinition $storageFieldDef
     * @param FieldDefinition $fieldDef
     */
    public function toFieldDefinition(
        StorageFieldDefinition $storageFieldDef,
        FieldDefinition $fieldDef,
        int $status = Type::STATUS_DEFINED
    ) {
        $converter = $this->converterRegistry->getConverter(
            $fieldDef->fieldType
        );
        $converter->toFieldDefinition(
            $storageFieldDef,
            $fieldDef
        );

        $this->storageDispatcher->loadFieldConstraintsData($fieldDef, $status);
    }

    /**
     * Wrap unserialize to set default value in case of empty serialization.
     *
     * @param string $serialized Serialized structure to process
     * @param mixed $default Default value in case of empty serialization
     *
     * @return array|mixed
     */
    protected function unserialize(
        $serialized,
        $default = []
    ) {
        return $serialized
            ? unserialize($serialized)
            : $default;
    }

    /**
     * @param UpdateStruct $updateStruct
     *
     * @return Type
     */
    public function createTypeFromUpdateStruct(UpdateStruct $updateStruct): Type
    {
        $type = new Type();

        $type->name = $updateStruct->name;
        $type->description = $updateStruct->description;
        $type->identifier = $updateStruct->identifier;
        $type->modified = $updateStruct->modified;
        $type->modifierId = $updateStruct->modifierId;
        $type->remoteId = $updateStruct->remoteId;
        $type->urlAliasSchema = $updateStruct->urlAliasSchema;
        $type->nameSchema = $updateStruct->nameSchema;
        $type->isContainer = $updateStruct->isContainer;
        $type->initialLanguageId = $updateStruct->initialLanguageId;
        $type->defaultAlwaysAvailable = $updateStruct->defaultAlwaysAvailable;
        $type->sortField = $updateStruct->sortField;
        $type->sortOrder = $updateStruct->sortOrder;
        $type->languageCodes = array_keys($updateStruct->name);

        return $type;
    }

    public function extractMultilingualDataFromRows(array $mlFieldDefinitionsRows): array
    {
        $mlFieldDefinitionData = [];
        foreach ($mlFieldDefinitionsRows as $row) {
            $mlStorageFieldDefinition = new MultilingualStorageFieldDefinition();
            $mlStorageFieldDefinition->name = $row['content_type_field_definition_multilingual_name'];
            $mlStorageFieldDefinition->description = $row['content_type_field_definition_multilingual_description'];
            $mlStorageFieldDefinition->languageId = $row['content_type_field_definition_multilingual_language_id'];
            $mlStorageFieldDefinition->dataText = $row['content_type_field_definition_multilingual_data_text'];
            $mlStorageFieldDefinition->dataJson = $row['content_type_field_definition_multilingual_data_json'];

            $mlFieldDefinitionData[] = $mlStorageFieldDefinition;
        }

        return $mlFieldDefinitionData;
    }
}
