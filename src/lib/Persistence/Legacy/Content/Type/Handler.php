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
use Ibexa\Contracts\Core\Persistence\Content\Type\Group\UpdateStruct as GroupUpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Type\Handler as BaseContentTypeHandler;
use Ibexa\Contracts\Core\Persistence\Content\Type\UpdateStruct;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\FieldType\FieldTypeAliasResolverInterface;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\Type\Update\Handler as UpdateHandler;
use Ibexa\Core\Persistence\Legacy\Exception;

class Handler implements BaseContentTypeHandler
{
    /** @var \Ibexa\Core\Persistence\Legacy\Content\Type\Gateway */
    protected $contentTypeGateway;

    /**
     * Mapper for Type objects.
     *
     * @var Mapper
     */
    protected $mapper;

    /**
     * Content type update handler.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Type\Update\Handler
     */
    protected $updateHandler;

    private StorageDispatcherInterface $storageDispatcher;

    /**
     * Creates a new content type handler.
     *
     * @param \Ibexa\Core\Persistence\Legacy\Content\Type\Gateway $contentTypeGateway
     * @param \Ibexa\Core\Persistence\Legacy\Content\Type\Mapper $mapper
     * @param \Ibexa\Core\Persistence\Legacy\Content\Type\Update\Handler $updateHandler
     */
    public function __construct(
        Gateway $contentTypeGateway,
        Mapper $mapper,
        UpdateHandler $updateHandler,
        StorageDispatcherInterface $storageDispatcher,
        private readonly FieldTypeAliasResolverInterface $fieldTypeAliasResolver
    ) {
        $this->contentTypeGateway = $contentTypeGateway;
        $this->mapper = $mapper;
        $this->updateHandler = $updateHandler;
        $this->storageDispatcher = $storageDispatcher;
    }

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\Group\CreateStruct $createStruct
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\Group
     */
    public function createGroup(GroupCreateStruct $createStruct)
    {
        $group = $this->mapper->createGroupFromCreateStruct(
            $createStruct
        );

        $group->id = $this->contentTypeGateway->insertGroup(
            $group
        );

        return $group;
    }

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\Group\UpdateStruct $struct
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\Group
     */
    public function updateGroup(GroupUpdateStruct $struct)
    {
        $this->contentTypeGateway->updateGroup(
            $struct
        );

        return $this->loadGroup($struct->id);
    }

    /**
     * @param mixed $groupId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException If type group contains types
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If type group with id is not found
     */
    public function deleteGroup($groupId)
    {
        if ($this->contentTypeGateway->countTypesInGroup($groupId) !== 0) {
            throw new Exception\GroupNotEmpty($groupId);
        }
        $this->contentTypeGateway->deleteGroup($groupId);
    }

    /**
     * @param mixed $groupId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If type group with $groupId is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\Group
     */
    public function loadGroup($groupId)
    {
        $groups = $this->mapper->extractGroupsFromRows(
            $this->contentTypeGateway->loadGroupData([$groupId])
        );

        if (count($groups) !== 1) {
            throw new Exception\TypeGroupNotFound((string)$groupId);
        }

        return $groups[0];
    }

    /**
     * {@inheritdoc}
     */
    public function loadGroups(array $groupIds)
    {
        $groups = $this->mapper->extractGroupsFromRows(
            $this->contentTypeGateway->loadGroupData($groupIds)
        );

        $listByGroupIds = [];
        foreach ($groups as $group) {
            $listByGroupIds[$group->id] = $group;
        }

        return $listByGroupIds;
    }

    /**
     * @param string $identifier
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If type group with $identifier is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\Group
     */
    public function loadGroupByIdentifier($identifier)
    {
        $groups = $this->mapper->extractGroupsFromRows(
            $this->contentTypeGateway->loadGroupDataByIdentifier($identifier)
        );

        if (count($groups) !== 1) {
            throw new Exception\TypeGroupNotFound($identifier);
        }

        return $groups[0];
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\Group[]
     */
    public function loadAllGroups()
    {
        return $this->mapper->extractGroupsFromRows(
            $this->contentTypeGateway->loadAllGroupsData()
        );
    }

    /**
     * @param mixed $groupId
     * @param int $status
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type[]
     */
    public function loadContentTypes($groupId, $status = 0)
    {
        return $this->mapper->extractTypesFromRows(
            $this->contentTypeGateway->loadTypesDataForGroup($groupId, $status)
        );
    }

    public function loadContentTypeList(array $contentTypeIds): array
    {
        return $this->mapper->extractTypesFromRows(
            $this->contentTypeGateway->loadTypesListData($contentTypeIds),
            true
        );
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type[]
     */
    public function loadContentTypesByFieldDefinitionIdentifier(string $identifier): array
    {
        return $this->mapper->extractTypesFromRows(
            $this->contentTypeGateway->loadTypesDataByFieldDefinitionIdentifier($identifier)
        );
    }

    /**
     * Loads a content type by id and status.
     *
     * Note: This method is responsible of having the Field Definitions of the loaded ContentType sorted by placement.
     *
     * @param int $contentTypeId
     * @param int $status
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type
     */
    public function load($contentTypeId, $status = Type::STATUS_DEFINED)
    {
        return $this->loadFromRows(
            $this->contentTypeGateway->loadTypeData($contentTypeId, $status),
            $contentTypeId,
            $status
        );
    }

    /**
     * Loads a (defined) content type by identifier.
     *
     * Note: This method is responsible of having the Field Definitions of the loaded ContentType sorted by placement.
     *
     * @param string $identifier
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If defined type is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type
     */
    public function loadByIdentifier($identifier)
    {
        $rows = $this->contentTypeGateway->loadTypeDataByIdentifier($identifier, Type::STATUS_DEFINED);

        return $this->loadFromRows($rows, $identifier, Type::STATUS_DEFINED);
    }

    /**
     * Loads a (defined) content type by remote id.
     *
     * Note: This method is responsible of having the Field Definitions of the loaded ContentType sorted by placement.
     *
     * @param mixed $remoteId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If defined type is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type
     */
    public function loadByRemoteId($remoteId)
    {
        return $this->loadFromRows(
            $this->contentTypeGateway->loadTypeDataByRemoteId($remoteId, Type::STATUS_DEFINED),
            $remoteId,
            Type::STATUS_DEFINED
        );
    }

    /**
     * Loads a single Type from $rows.
     *
     * @param array $rows
     * @param mixed $typeIdentifier
     * @param int $status
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type
     */
    protected function loadFromRows(array $rows, $typeIdentifier, $status)
    {
        $types = $this->mapper->extractTypesFromRows($rows);
        if (count($types) !== 1) {
            throw new Exception\TypeNotFound($typeIdentifier, $status);
        }

        return $types[0];
    }

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\CreateStruct $createStruct
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type
     */
    public function create(CreateStruct $createStruct)
    {
        return $this->internalCreate($createStruct);
    }

    /**
     * Internal method for creating ContentType.
     *
     * Used by self::create(), self::createDraft() and self::copy()
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\CreateStruct $createStruct
     * @param mixed|null $contentTypeId Used by self::createDraft() to retain ContentType id in the draft
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type
     */
    protected function internalCreate(CreateStruct $createStruct, $contentTypeId = null)
    {
        foreach ($createStruct->fieldDefinitions as $fieldDef) {
            if (!is_int($fieldDef->position)) {
                throw new InvalidArgumentException(
                    'position',
                    "'" . var_export($fieldDef->position, true) .
                    "' is incorrect value in class FieldDefinition, an integer is required."
                );
            }
        }

        $createStruct = clone $createStruct;
        $contentType = $this->mapper->createTypeFromCreateStruct(
            $createStruct
        );

        $contentType->id = $this->contentTypeGateway->insertType(
            $contentType,
            $contentTypeId
        );

        foreach ($contentType->groupIds as $groupId) {
            $this->contentTypeGateway->insertGroupAssignment(
                $groupId,
                $contentType->id,
                $contentType->status
            );
        }

        foreach ($contentType->fieldDefinitions as $fieldDef) {
            $storageFieldDef = new StorageFieldDefinition();
            $this->mapper->toStorageFieldDefinition($fieldDef, $storageFieldDef);
            $fieldDef->id = $this->contentTypeGateway->insertFieldDefinition(
                $contentType->id,
                $contentType->status,
                $fieldDef,
                $storageFieldDef
            );

            $this->storageDispatcher->storeFieldConstraintsData($fieldDef, $contentType->status);
        }

        return $contentType;
    }

    /**
     * @param mixed $typeId
     * @param int $status
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\UpdateStruct $updateStruct
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type
     */
    public function update($typeId, $status, UpdateStruct $updateStruct)
    {
        $contentType = $this->mapper->createTypeFromUpdateStruct(
            $updateStruct
        );
        $this->contentTypeGateway->updateType($typeId, $status, $contentType);

        return $this->load($typeId, $status);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException If type is defined and still has content
     *
     * @param mixed $contentTypeId
     * @param int $status
     *
     * @return bool
     */
    public function delete($contentTypeId, $status): bool
    {
        if (Type::STATUS_DEFINED === $status && $this->contentTypeGateway->countInstancesOfType($contentTypeId)) {
            throw new BadStateException(
                '$contentTypeId',
                'Content type with the given ID still has Content items and cannot be deleted'
            );
        }

        try {
            $fieldDefinitions = $this->load($contentTypeId, $status)->fieldDefinitions;
        } catch (Exception\TypeNotFound $e) {
            $fieldDefinitions = [];
        }

        foreach ($fieldDefinitions as $fieldDefinition) {
            $this->storageDispatcher->deleteFieldConstraintsData($fieldDefinition->fieldType, $fieldDefinition->id, $status);
        }

        $this->contentTypeGateway->delete($contentTypeId, $status);

        // @todo FIXME: Return true only if deletion happened
        return true;
    }

    /**
     * Creates a draft of existing defined content type.
     *
     * Updates modified date, sets $modifierId and status to Type::STATUS_DRAFT on the new returned draft.
     *
     * @param mixed $modifierId
     * @param mixed $contentTypeId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If type with defined status is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type
     */
    public function createDraft($modifierId, $contentTypeId)
    {
        $createStruct = $this->mapper->createCreateStructFromType(
            $this->load($contentTypeId, Type::STATUS_DEFINED)
        );
        $createStruct->status = Type::STATUS_DRAFT;
        $createStruct->modifierId = $modifierId;
        $createStruct->modified = time();

        return $this->internalCreate($createStruct, $contentTypeId);
    }

    /**
     * @param mixed $userId
     * @param mixed $contentTypeId
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type
     */
    public function copy($userId, $contentTypeId, $status)
    {
        $createStruct = $this->mapper->createCreateStructFromType(
            $this->load($contentTypeId, $status)
        );
        $createStruct->modifierId = $userId;
        $createStruct->created = $createStruct->modified = time();
        $createStruct->creatorId = $userId;
        $createStruct->remoteId = md5(uniqid(get_class($createStruct), true));

        // extract actual identifier name, without "copy_of_" and number
        $originalIdentifier = preg_replace('/^copy_of_(.+)_\d+$/', '$1', $createStruct->identifier);

        // set temporary identifier
        $createStruct->identifier = $createStruct->remoteId;

        // Set FieldDefinition ids to null to trigger creating new id
        foreach ($createStruct->fieldDefinitions as $fieldDefinition) {
            $fieldDefinition->id = null;
        }

        $contentTypeCopy = $this->internalCreate($createStruct);
        $updateStruct = $this->mapper->createUpdateStructFromType($contentTypeCopy);
        $updateStruct->identifier = 'copy_of_' . $originalIdentifier . '_' . $contentTypeCopy->id;

        return $this->update($contentTypeCopy->id, $contentTypeCopy->status, $updateStruct);
    }

    /**
     * Unlink a content type group from a content type.
     *
     * @param mixed $groupId
     * @param mixed $contentTypeId
     * @param int $status
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If group or type with provided status is not found
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException If $groupId is last group on $contentTypeId or
     *                                                                 not a group assigned to type
     *
     * @todo Add throws for NotFound and BadState when group is not assigned to type
     */
    public function unlink($groupId, $contentTypeId, $status): bool
    {
        $groupCount = $this->contentTypeGateway->countGroupsForType($contentTypeId, $status);
        if ($groupCount < 2) {
            throw new Exception\RemoveLastGroupFromType($contentTypeId, $status);
        }

        $this->contentTypeGateway->deleteGroupAssignment($groupId, $contentTypeId, $status);

        // @todo FIXME: What is to be returned?
        return true;
    }

    /**
     * Link a content type group with a content type.
     *
     * @param mixed $groupId
     * @param mixed $contentTypeId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If group or type with provided status is not found
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException If type is already part of group
     *
     * @todo Above throws are not implemented
     */
    public function link($groupId, $contentTypeId, $status): bool
    {
        $this->contentTypeGateway->insertGroupAssignment($groupId, $contentTypeId, $status);

        // @todo FIXME: What is to be returned?
        return true;
    }

    /**
     * Returns field definition for the given field definition id.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If field definition is not found
     *
     * @param mixed $id
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition
     */
    public function getFieldDefinition($id, $status)
    {
        $rows = $this->contentTypeGateway->loadFieldDefinition($id, $status);

        if (count($rows) === 0) {
            throw new NotFoundException(
                'FieldDefinition',
                [
                    'id' => $id,
                    'status' => $status,
                ]
            );
        }

        $multilingualData = $this->mapper->extractMultilingualData($rows);

        return $this->mapper->extractFieldFromRow(reset($rows), $multilingualData, $status);
    }

    /**
     * Counts the number of Content instances of the ContentType identified by given $contentTypeId.
     *
     * @param mixed $contentTypeId
     *
     * @return int
     */
    public function getContentCount($contentTypeId)
    {
        return $this->contentTypeGateway->countInstancesOfType($contentTypeId);
    }

    /**
     * Adds a new field definition to an existing Type.
     *
     * This method creates a new status of the Type with the $fieldDefinition
     * added. It does not update existing content objects depending on the
     * field (default) values.
     *
     * @param mixed $contentTypeId
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition $fieldDefinition
     */
    public function addFieldDefinition($contentTypeId, $status, FieldDefinition $fieldDefinition)
    {
        $storageFieldDef = new StorageFieldDefinition();
        $this->mapper->toStorageFieldDefinition($fieldDefinition, $storageFieldDef);
        $fieldDefinition->id = $this->contentTypeGateway->insertFieldDefinition(
            $contentTypeId,
            $status,
            $fieldDefinition,
            $storageFieldDef
        );

        $this->storageDispatcher->storeFieldConstraintsData($fieldDefinition, $status);
    }

    /**
     * Removes a field definition from an existing Type.
     *
     * This method creates a new status of the Type with the field definition
     * referred to by $fieldDefinitionId removed. It does not update existing
     * content objects depending on the field (default) values.
     *
     * @param mixed $contentTypeId
     * @param mixed $fieldDefinitionId
     *
     * @return bool
     */
    public function removeFieldDefinition(
        int $contentTypeId,
        int $status,
        FieldDefinition $fieldDefinition
    ): void {
        $this->storageDispatcher->deleteFieldConstraintsData(
            $fieldDefinition->fieldType,
            $fieldDefinition->id,
            $status
        );

        $this->contentTypeGateway->deleteFieldDefinition(
            $contentTypeId,
            $status,
            $fieldDefinition->id
        );
    }

    /**
     * This method updates the given $fieldDefinition on a Type.
     *
     * This method creates a new status of the Type with the updated
     * $fieldDefinition. It does not update existing content objects depending
     * on the
     * field (default) values.
     *
     * @param mixed $contentTypeId
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition $fieldDefinition
     */
    public function updateFieldDefinition($contentTypeId, $status, FieldDefinition $fieldDefinition)
    {
        $storageFieldDef = new StorageFieldDefinition();
        $this->mapper->toStorageFieldDefinition($fieldDefinition, $storageFieldDef);
        $this->contentTypeGateway->updateFieldDefinition($contentTypeId, $status, $fieldDefinition, $storageFieldDef);
        $this->storageDispatcher->storeFieldConstraintsData($fieldDefinition, $status);
    }

    /**
     * Update content objects.
     *
     * Updates content objects, depending on the changed field definitions.
     *
     * A content type has a state which tells if its content objects yet have
     * been adapted.
     *
     * Flags the content type as updated.
     *
     * @param mixed $contentTypeId
     */
    public function publish($contentTypeId)
    {
        $toType = $this->load($contentTypeId, Type::STATUS_DRAFT);

        try {
            $fromType = $this->load($contentTypeId, Type::STATUS_DEFINED);
            $this->updateHandler->deleteOldType($fromType);
        } catch (Exception\TypeNotFound $e) {
            // If no old type is found, no updates are necessary to it
        }

        $this->updateHandler->publishNewType($toType, Type::STATUS_DEFINED);

        foreach ($toType->fieldDefinitions as $fieldDefinition) {
            $this->storageDispatcher->publishFieldConstraintsData($fieldDefinition);
        }
    }

    public function getSearchableFieldMap()
    {
        $fieldMap = [];
        $rows = $this->contentTypeGateway->getSearchableFieldMapData();

        foreach ($rows as $row) {
            $fieldTypeIdentifier = $row['field_type_identifier'];
            $fieldTypeIdentifier = $this->fieldTypeAliasResolver->resolveIdentifier($fieldTypeIdentifier);

            $fieldMap[$row['content_type_identifier']][$row['field_definition_identifier']] = [
                'field_type_identifier' => $fieldTypeIdentifier,
                'field_definition_id' => $row['field_definition_id'],
            ];
        }

        return $fieldMap;
    }

    /**
     * @param int $contentTypeId
     * @param string $languageCode
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type
     */
    public function removeContentTypeTranslation(int $contentTypeId, string $languageCode): Type
    {
        $type = $this->load($contentTypeId, Type::STATUS_DRAFT);

        unset($type->name[$languageCode]);
        unset($type->description[$languageCode]);

        foreach ($type->fieldDefinitions as $fieldDefinition) {
            $this->contentTypeGateway->removeFieldDefinitionTranslation(
                $fieldDefinition->id,
                $languageCode,
                Type::STATUS_DRAFT
            );

            //Refresh FieldDefinition object after removing translation data.
            $fieldDefinition = $this->getFieldDefinition(
                $fieldDefinition->id,
                Type::STATUS_DRAFT
            );
            unset($fieldDefinition->name[$languageCode]);
            unset($fieldDefinition->description[$languageCode]);
            $storageFieldDefinition = new StorageFieldDefinition();
            $this->mapper->toStorageFieldDefinition($fieldDefinition, $storageFieldDefinition);
            $this->contentTypeGateway->updateFieldDefinition(
                $contentTypeId,
                Type::STATUS_DRAFT,
                $fieldDefinition,
                $storageFieldDefinition
            );
        }

        $updateStruct = $this->mapper->createUpdateStructFromType($type);

        return $this->update($type->id, Type::STATUS_DRAFT, $updateStruct);
    }

    public function deleteByUserAndStatus(int $userId, int $status): void
    {
        $this->contentTypeGateway->removeByUserAndVersion($userId, $status);
    }
}
