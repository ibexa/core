<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Persistence\Content\Section;

use Ibexa\Contracts\Core\Persistence\Content\Section;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;

interface Handler
{
    /**
     * Create a new section.
     *
     * @param string $name
     * @param string $identifier
     *
     * @return Section
     *
     * @todo Should validate that $identifier is unique??
     * @todo What about translatable $name?
     */
    public function create(
        $name,
        $identifier
    );

    /**
     * Update name and identifier of a section.
     *
     * @param mixed $id
     * @param string $name
     * @param string $identifier
     *
     * @return Section
     */
    public function update(
        $id,
        $name,
        $identifier
    );

    /**
     * Get section data.
     *
     * @param mixed $id
     *
     * @throws NotFoundException If section is not found
     *
     * @return Section
     */
    public function load($id);

    /**
     * Get all section data.
     *
     * @return Section[]
     */
    public function loadAll();

    /**
     * Get section data by identifier.
     *
     * @param string $identifier
     *
     * @throws NotFoundException If section is not found
     *
     * @return Section
     */
    public function loadByIdentifier($identifier);

    /**
     * Delete a section.
     *
     * Might throw an exception if the section is still associated with some
     * content objects. Make sure that no content objects are associated with
     * the section any more *before* calling this method.
     *
     * @param mixed $id
     */
    public function delete($id);

    /**
     * Assigns section to single content object.
     *
     * @param mixed $sectionId
     * @param mixed $contentId
     */
    public function assign(
        $sectionId,
        $contentId
    );

    /**
     * Number of content assignments a Section has.
     *
     * @param mixed $sectionId
     *
     * @return int
     */
    public function assignmentsCount($sectionId);

    /**
     * Number of role policies using a Section in limitations.
     *
     * @param mixed $sectionId
     *
     * @return int
     */
    public function policiesCount($sectionId);

    /**
     * Counts the number of role assignments using section with $sectionId in their limitations.
     *
     * @param int $sectionId
     *
     * @return int
     */
    public function countRoleAssignmentsUsingSection($sectionId);
}
