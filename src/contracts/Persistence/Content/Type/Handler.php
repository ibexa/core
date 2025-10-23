<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Persistence\Content\Type;

use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group\CreateStruct as GroupCreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Type\Group\UpdateStruct as GroupUpdateStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\ContentTypeQuery;

interface Handler
{
    /**
     * @param GroupCreateStruct $group
     *
     * @return Group
     */
    public function createGroup(GroupCreateStruct $group);

    /**
     * @param GroupUpdateStruct $group
     */
    public function updateGroup(GroupUpdateStruct $group);

    /**
     * @param mixed $groupId
     *
     * @throws BadStateException If type group contains types
     * @throws NotFoundException If type group with id is not found
     */
    public function deleteGroup($groupId);

    /**
     * @param mixed $groupId
     *
     * @throws NotFoundException If type group with id is not found
     *
     * @return Group
     */
    public function loadGroup($groupId);

    /**
     * Return list of unique content type groups, with group id as key.
     *
     * Missing items (NotFound) will be missing from the array and not cause an exception, it's up
     * to calling logic to determine if this should cause exception or not.
     *
     * @param array $groupIds
     *
     * @return Group[]
     */
    public function loadGroups(array $groupIds);

    /**
     * Loads Type Group by identifier.
     *
     * Legacy note: Uses name for identifier.
     *
     * @param string $identifier
     *
     * @throws NotFoundException If type group with id is not found
     *
     * @return Group
     */
    public function loadGroupByIdentifier($identifier);

    /**
     * @return Group[]
     */
    public function loadAllGroups();

    /**
     * @param mixed $groupId
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     *
     * @return Type[]
     */
    public function loadContentTypes(
        $groupId,
        $status = Type::STATUS_DEFINED
    );

    /**
     * Return list of unique content types, with type id as key.
     *
     * Missing items (NotFound) will be missing from the array and not cause an exception, it's up
     * to calling logic to determine if this should cause exception or not.
     *
     * @param array $contentTypeIds
     *
     * @return Type[]
     */
    public function loadContentTypeList(array $contentTypeIds): array;

    /**
     * @param list<string> $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @return array{count: int, items: array<string, mixed>}
     */
    public function findContentTypes(
        ?ContentTypeQuery $query = null,
        array $prioritizedLanguages = []
    ): array;

    /**
     * @return Type[]
     */
    public function loadContentTypesByFieldDefinitionIdentifier(string $identifier): array;

    /**
     * Loads a content type by id and status.
     *
     * Note: This method is responsible of having the Field Definitions of the loaded ContentType sorted by placement.
     *
     * @param mixed $contentTypeId
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     *
     * @throws NotFoundException If type with provided status is not found
     *
     * @return Type
     */
    public function load(
        $contentTypeId,
        $status = Type::STATUS_DEFINED
    );

    /**
     * Loads a (defined) content type by identifier.
     *
     * Note: This method is responsible of having the Field Definitions of the loaded ContentType sorted by placement.
     *
     * @param string $identifier
     *
     * @throws NotFoundException If defined type is not found
     *
     * @return Type
     */
    public function loadByIdentifier($identifier);

    /**
     * Loads a (defined) content type by remote id.
     *
     * Note: This method is responsible of having the Field Definitions of the loaded ContentType sorted by placement.
     *
     * @param mixed $remoteId
     *
     * @throws NotFoundException If defined type is not found
     *
     * @return Type
     */
    public function loadByRemoteId($remoteId);

    /**
     * @param CreateStruct $contentType
     *
     * @return Type
     */
    public function create(CreateStruct $contentType);

    /**
     * @param mixed $contentTypeId
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     * @param UpdateStruct $contentType
     */
    public function update(
        $contentTypeId,
        $status,
        UpdateStruct $contentType
    );

    /**
     * @param mixed $contentTypeId
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     *
     * @throws BadStateException If type is defined and still has content
     */
    public function delete(
        $contentTypeId,
        $status
    );

    /**
     * Creates a draft of existing defined content type.
     *
     * Updates modified date, sets $modifierId and status to Type::STATUS_DRAFT on the new returned draft.
     *
     * @param mixed $modifierId
     * @param mixed $contentTypeId
     *
     * @throws NotFoundException If type with defined status is not found
     *
     * @return Type
     */
    public function createDraft(
        $modifierId,
        $contentTypeId
    );

    /**
     * Copy a Type to a new Type with status Draft.
     *
     * Copy a Type incl fields and group-relations from a given status to a new Type with status {@see Type::STATUS_DRAFT}.
     *
     * New content type will have $userId as creator / modifier, created / modified should be updated, new remoteId created
     * and identifier should be 'copy_of_<originalBaseIdentifier>_<newTypeId>' or another unique string.
     *
     * @param mixed $userId
     * @param mixed $contentTypeId
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     *
     * @throws NotFoundException If user or type with provided status is not found
     *
     * @return Type
     */
    public function copy(
        $userId,
        $contentTypeId,
        $status
    );

    /**
     * Unlink a content type group from a content type.
     *
     * @param mixed $groupId
     * @param mixed $contentTypeId
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     *
     * @throws NotFoundException If group or type with provided status is not found
     * @throws BadStateException If $groupId is last group on $contentTypeId or
     *                                                                 not a group assigned to type
     */
    public function unlink(
        $groupId,
        $contentTypeId,
        $status
    );

    /**
     * Link a content type group with a content type.
     *
     * @param mixed $groupId
     * @param mixed $contentTypeId
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     *
     * @throws NotFoundException If group or type with provided status is not found
     * @throws BadStateException If type is already part of group
     */
    public function link(
        $groupId,
        $contentTypeId,
        $status
    );

    /**
     * Returns field definition for the given field definition id.
     *
     * @throws NotFoundException If field definition is not found
     *
     * @param mixed $id
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     *
     * @return FieldDefinition
     */
    public function getFieldDefinition(
        $id,
        $status
    );

    /**
     * Counts the number of Content instances of the ContentType identified by given $contentTypeId.
     *
     * @param mixed $contentTypeId
     *
     * @return int
     */
    public function getContentCount($contentTypeId);

    /**
     * Adds a new field definition to an existing Type.
     *
     * This method creates a new version of the Type with the $fieldDefinition
     * added. It does not update existing content objects depending on the
     * field (default) values.
     *
     * @param mixed $contentTypeId
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     * @param FieldDefinition $fieldDefinition
     *
     * @return FieldDefinition
     *
     * @throws NotFoundException If type is not found
     *
     * @todo Add FieldDefinition\CreateStruct?
     */
    public function addFieldDefinition(
        $contentTypeId,
        $status,
        FieldDefinition $fieldDefinition
    );

    /**
     * Removes a field definition from an existing Type.
     *
     * This method creates a new version of the Type with the field definition
     * referred to by $fieldDefinitionId removed. It does not update existing
     * content objects depending on the field (default) values.
     *
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     *
     * @throws NotFoundException If field is not found
     */
    public function removeFieldDefinition(
        int $contentTypeId,
        int $status,
        FieldDefinition $fieldDefinition
    ): void;

    /**
     * This method updates the given $fieldDefinition on a Type.
     *
     * This method creates a new version of the Type with the updated
     * $fieldDefinition. It does not update existing content objects depending
     * on the
     * field (default) values.
     *
     * @param mixed $contentTypeId
     * @param int $status One of Type::STATUS_DEFINED|Type::STATUS_DRAFT|Type::STATUS_MODIFIED
     * @param FieldDefinition $fieldDefinition
     *
     * @throws NotFoundException If field is not found
     *
     * @todo Add FieldDefinition\UpdateStruct?
     */
    public function updateFieldDefinition(
        $contentTypeId,
        $status,
        FieldDefinition $fieldDefinition
    );

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
     *
     * @throws NotFoundException If type with $contentTypeId and Type::STATUS_DRAFT is not found
     */
    public function publish($contentTypeId);

    /**
     * Returns content type, field definition and field type mapping information
     * for search engine usage. Only searchable field definitions will be included
     * in the returned data.
     *
     * Returns an array in the form:
     *
     * <code>
     *  array(
     *      "<ContentType identifier>" => array(
     *          "<FieldDefinition identifier>" => array(
     *              "field_definition_id" => "<FieldDefinition id>",
     *              "field_type_identifier" => "<FieldType identifier>",
     *          ),
     *          ...
     *      ),
     *      ...
     *  )
     * </code>
     *
     * @return array
     */
    public function getSearchableFieldMap();

    /**
     * @param int $contentTypeId
     * @param string $languageCode
     *
     * @return Type
     */
    public function removeContentTypeTranslation(
        int $contentTypeId,
        string $languageCode
    ): Type;

    /**
     * @param int $userId
     * @param int $status
     */
    public function deleteByUserAndStatus(
        int $userId,
        int $status
    ): void;
}
