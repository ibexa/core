<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Persistence\Content\ObjectState;

/**
 * The Object State Handler interface provides managing of object states and groups.
 */
interface Handler
{
    /**
     * Creates a new object state group.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\ObjectState\InputStruct $input
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group
     */
    public function createGroup(InputStruct $input);

    /**
     * Loads a object state group.
     *
     * @param mixed $groupId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the group was not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group
     */
    public function loadGroup($groupId);

    /**
     * Loads a object state group by identifier.
     *
     * @param string $identifier
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the group was not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group
     */
    public function loadGroupByIdentifier($identifier);

    /**
     * Loads all object state groups.
     *
     * @param int $offset
     * @param int $limit
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group[]
     */
    public function loadAllGroups($offset = 0, $limit = -1);

    /**
     * This method returns the ordered list of object states of a group.
     *
     * @param mixed $groupId
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState[]
     */
    public function loadObjectStates($groupId);

    /**
     * Updates an object state group.
     *
     * @param mixed $groupId
     * @param \Ibexa\Contracts\Core\Persistence\Content\ObjectState\InputStruct $input
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group
     */
    public function updateGroup($groupId, InputStruct $input);

    /**
     * Deletes a object state group including all states and links to content.
     *
     * @param mixed $groupId
     */
    public function deleteGroup($groupId);

    /**
     * Creates a new object state in the given group.
     * The new state gets the last priority.
     * Note: in current kernel: If it is the first state all content objects will
     * set to this state.
     *
     * @param mixed $groupId
     * @param \Ibexa\Contracts\Core\Persistence\Content\ObjectState\InputStruct $input
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState
     */
    public function create($groupId, InputStruct $input);

    /**
     * Loads an object state.
     *
     * @param mixed $stateId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the state was not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState
     */
    public function load($stateId);

    /**
     * Loads an object state by identifier and group it belongs to.
     *
     * @param string $identifier
     * @param mixed $groupId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the state was not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState
     */
    public function loadByIdentifier($identifier, $groupId);

    /**
     * Updates an object state.
     *
     * @param mixed $stateId
     * @param \Ibexa\Contracts\Core\Persistence\Content\ObjectState\InputStruct $input
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState
     */
    public function update($stateId, InputStruct $input);

    /**
     * Changes the priority of the state.
     *
     * @param mixed $stateId
     * @param int $priority
     */
    public function setPriority($stateId, $priority);

    /**
     * Deletes a object state. The state of the content objects is reset to the
     * first object state in the group.
     *
     * @param mixed $stateId
     */
    public function delete($stateId);

    /**
     * Sets the object-state of a state group to $stateId for the given content.
     *
     * @param mixed $contentId
     * @param mixed $groupId
     * @param mixed $stateId
     *
     * @return bool
     */
    public function setContentState($contentId, $groupId, $stateId);

    /**
     * Gets the object-state of object identified by $contentId.
     *
     * The $state is the id of the state within one group.
     *
     * @param mixed $contentId
     * @param mixed $stateGroupId
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\ObjectState
     */
    public function getContentState($contentId, $stateGroupId);

    /**
     * Returns the number of objects which are in this state.
     *
     * @param mixed $stateId
     *
     * @return int
     */
    public function getContentCount($stateId);
}
