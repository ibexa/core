<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\Section;
use Ibexa\Contracts\Core\Repository\Values\Content\SectionCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\SectionUpdateStruct;

/**
 * Section service, used for section operations.
 */
interface SectionService
{
    /**
     * Creates the a new Section in the content repository.
     *
     * @throws UnauthorizedException If the current user user is not allowed to create a section
     * @throws InvalidArgumentException If the new identifier in $sectionCreateStruct already exists
     *
     * @param SectionCreateStruct $sectionCreateStruct
     *
     * @return Section The newly create section
     */
    public function createSection(SectionCreateStruct $sectionCreateStruct): Section;

    /**
     * Updates the given in the content repository.
     *
     * @throws UnauthorizedException If the current user user is not allowed to create a section
     * @throws InvalidArgumentException If the new identifier already exists (if set in the update struct)
     *
     * @param Section $section
     * @param SectionUpdateStruct $sectionUpdateStruct
     *
     * @return Section
     */
    public function updateSection(
        Section $section,
        SectionUpdateStruct $sectionUpdateStruct
    ): Section;

    /**
     * Loads a Section from its id ($sectionId).
     *
     * @throws NotFoundException if section could not be found
     * @throws UnauthorizedException If the current user user is not allowed to read a section
     *
     * @param int $sectionId
     *
     * @return Section
     */
    public function loadSection(int $sectionId): Section;

    /**
     * Loads all sections, excluding the ones the current user is not allowed to read.
     *
     * @return Section[]
     */
    public function loadSections(): iterable;

    /**
     * Loads a Section from its identifier ($sectionIdentifier).
     *
     * @throws NotFoundException if section could not be found
     * @throws UnauthorizedException If the current user user is not allowed to read a section
     *
     * @param string $sectionIdentifier
     *
     * @return Section
     */
    public function loadSectionByIdentifier(string $sectionIdentifier): Section;

    /**
     * Counts the contents which $section is assigned to.
     */
    public function countAssignedContents(Section $section): int;

    /**
     * Returns true if the given section is assigned to contents, or used in role policies, or in role assignments.
     *
     * This does not check user permissions.
     *
     * @since 6.0
     *
     * @param Section $section
     *
     * @return bool
     */
    public function isSectionUsed(Section $section): bool;

    /**
     * Assigns the content to the given section this method overrides the current assigned section.
     *
     * @throws UnauthorizedException If user does not have access to view provided object
     *
     * @param ContentInfo $contentInfo
     * @param Section $section
     */
    public function assignSection(
        ContentInfo $contentInfo,
        Section $section
    ): void;

    /**
     * Assigns the subtree to the given section this method overrides the current assigned section.
     *
     * @param Location $location
     * @param Section $section
     */
    public function assignSectionToSubtree(
        Location $location,
        Section $section
    ): void;

    /**
     * Deletes $section from content repository.
     *
     * @throws NotFoundException If the specified section is not found
     * @throws UnauthorizedException If the current user user is not allowed to delete a section
     * @throws BadStateException  if section can not be deleted
     *         because it is still assigned to some contents.
     *
     * @param Section $section
     */
    public function deleteSection(Section $section): void;

    /**
     * Instantiates a new SectionCreateStruct.
     *
     * @return SectionCreateStruct
     */
    public function newSectionCreateStruct(): SectionCreateStruct;

    /**
     * Instantiates a new SectionUpdateStruct.
     *
     * @return SectionUpdateStruct
     */
    public function newSectionUpdateStruct(): SectionUpdateStruct;
}
