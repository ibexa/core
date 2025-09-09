<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type;

use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group\UpdateStruct as GroupUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\ContentTypeQuery;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;

/**
 * Content type Gateway.
 *
 * @internal For internal use by Persistence Handlers.
 */
abstract class Gateway
{
    public const CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE = 'ezcontentclass_classgroup';
    public const CONTENT_TYPE_GROUP_TABLE = 'ezcontentclassgroup';
    public const CONTENT_TYPE_TABLE = 'ezcontentclass';
    public const CONTENT_TYPE_NAME_TABLE = 'ezcontentclass_name';
    public const FIELD_DEFINITION_TABLE = 'ezcontentclass_attribute';
    public const MULTILINGUAL_FIELD_DEFINITION_TABLE = 'ezcontentclass_attribute_ml';

    public const CONTENT_TYPE_GROUP_SEQ = 'ezcontentclassgroup_id_seq';
    public const CONTENT_TYPE_SEQ = 'ezcontentclass_id_seq';
    public const FIELD_DEFINITION_SEQ = 'ezcontentclass_attribute_id_seq';

    abstract public function insertGroup(Group $group): int;

    abstract public function updateGroup(GroupUpdateStruct $group): void;

    abstract public function countTypes(?ContentTypeQuery $query = null): int;

    abstract public function countTypesInGroup(int $groupId): int;

    abstract public function countGroupsForType(int $typeId, int $status): int;

    abstract public function deleteGroup(int $groupId): void;

    /**
     * @param int[] $groupIds
     */
    abstract public function loadGroupData(array $groupIds): array;

    abstract public function loadGroupDataByIdentifier(string $identifier): array;

    abstract public function loadAllGroupsData(): array;

    /**
     * Load data for all content types of the given status, belonging to the given Group.
     */
    abstract public function loadTypesDataForGroup(int $groupId, int $status): array;

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the given language does not exist
     */
    abstract public function insertType(Type $type, ?int $typeId = null): int;

    /**
     * Assign a content type of the given status (published, draft) to content type group.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the given Group does not exist
     */
    abstract public function insertGroupAssignment(int $groupId, int $typeId, int $status): void;

    /**
     * Delete a Group assignments for content type of the given status (published, draft).
     */
    abstract public function deleteGroupAssignment(int $groupId, int $typeId, int $status): void;

    /**
     * @param int $id Field Definition ID
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     */
    abstract public function loadFieldDefinition(int $id, int $status): array;

    /**
     * Insert a Field Definition into content type.
     */
    abstract public function insertFieldDefinition(
        int $typeId,
        int $status,
        FieldDefinition $fieldDefinition,
        StorageFieldDefinition $storageFieldDef
    ): int;

    abstract public function deleteFieldDefinition(
        int $typeId,
        int $status,
        int $fieldDefinitionId
    ): void;

    abstract public function updateFieldDefinition(
        int $typeId,
        int $status,
        FieldDefinition $fieldDefinition,
        StorageFieldDefinition $storageFieldDef
    ): void;

    /**
     * Update a content type based on the given SPI Persistence Type Value Object.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if at least one of the used languages does not exist
     */
    abstract public function updateType(int $typeId, int $status, Type $type): void;

    /**
     * Bulk-load an array with data about the given content types.
     *
     * @param int[] $typeIds
     */
    abstract public function loadTypesListData(array $typeIds): array;

    /**
     * @return array<mixed>
     */
    abstract public function loadTypesDataByFieldDefinitionIdentifier(string $identifier): array;

    abstract public function loadTypeData(int $typeId, int $status): array;

    abstract public function loadTypeDataByIdentifier(string $identifier, int $status): array;

    abstract public function loadTypeDataByRemoteId(string $remoteId, int $status): array;

    abstract public function countInstancesOfType(int $typeId): int;

    /**
     * Permanently delete a content type of the given status.
     */
    abstract public function delete(int $typeId, int $status): void;

    abstract public function deleteFieldDefinitionsForType(int $typeId, int $status): void;

    /**
     * Delete a content type.
     *
     * Does not delete Field Definitions!
     */
    abstract public function deleteType(int $typeId, int $status): void;

    abstract public function deleteGroupAssignmentsForType(int $typeId, int $status): void;

    /**
     * Publish a content type including its Field Definitions.
     */
    abstract public function publishTypeAndFields(
        int $typeId,
        int $sourceStatus,
        int $targetStatus
    ): void;

    abstract public function getSearchableFieldMapData(): array;

    /**
     * Remove Field Definition data from multilingual table.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the given language does not exist
     */
    abstract public function removeFieldDefinitionTranslation(
        int $fieldDefinitionId,
        string $languageCode,
        int $status
    ): void;

    /**
     * Remove items created or modified by User.
     */
    abstract public function removeByUserAndVersion(int $userId, int $version): void;

    /**
     * @return array{items: array<int,array<string,mixed>>, count: int}
     */
    abstract public function findContentTypes(?ContentTypeQuery $query = null): array;
}

class_alias(Gateway::class, 'eZ\Publish\Core\Persistence\Legacy\Content\Type\Gateway');
