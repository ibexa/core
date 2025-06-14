<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Decorator;

use Ibexa\Contracts\Core\Repository\SectionService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\Section;
use Ibexa\Contracts\Core\Repository\Values\Content\SectionCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\SectionUpdateStruct;

abstract class SectionServiceDecorator implements SectionService
{
    protected SectionService $innerService;

    public function __construct(SectionService $innerService)
    {
        $this->innerService = $innerService;
    }

    public function createSection(SectionCreateStruct $sectionCreateStruct): Section
    {
        return $this->innerService->createSection($sectionCreateStruct);
    }

    public function updateSection(
        Section $section,
        SectionUpdateStruct $sectionUpdateStruct
    ): Section {
        return $this->innerService->updateSection($section, $sectionUpdateStruct);
    }

    public function loadSection(int $sectionId): Section
    {
        return $this->innerService->loadSection($sectionId);
    }

    public function loadSections(): iterable
    {
        return $this->innerService->loadSections();
    }

    public function loadSectionByIdentifier(string $sectionIdentifier): Section
    {
        return $this->innerService->loadSectionByIdentifier($sectionIdentifier);
    }

    public function countAssignedContents(Section $section): int
    {
        return $this->innerService->countAssignedContents($section);
    }

    public function isSectionUsed(Section $section): bool
    {
        return $this->innerService->isSectionUsed($section);
    }

    public function assignSection(
        ContentInfo $contentInfo,
        Section $section
    ): void {
        $this->innerService->assignSection($contentInfo, $section);
    }

    public function assignSectionToSubtree(
        Location $location,
        Section $section
    ): void {
        $this->innerService->assignSectionToSubtree($location, $section);
    }

    public function deleteSection(Section $section): void
    {
        $this->innerService->deleteSection($section);
    }

    public function newSectionCreateStruct(): SectionCreateStruct
    {
        return $this->innerService->newSectionCreateStruct();
    }

    public function newSectionUpdateStruct(): SectionUpdateStruct
    {
        return $this->innerService->newSectionUpdateStruct();
    }
}
