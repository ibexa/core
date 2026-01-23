<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\ContentService;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationList;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationList\RelationListItemInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\ContentService\RelationListFacade;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RelationListFacadeTest extends TestCase
{
    private ContentService & MockObject $contentService;

    private RelationListFacade $relationListFacade;

    private VersionInfo & MockObject $versionInfo;

    protected function setUp(): void
    {
        $this->contentService = $this->createMock(ContentService::class);
        $this->versionInfo = $this->createMock(VersionInfo::class);
        $this->relationListFacade = new RelationListFacade($this->contentService);
    }

    public function testGetRelationsReturnsEmptyIteratorWhenNoRelations(): void
    {
        $relationList = $this->createMock(RelationList::class);
        $relationList->method('getIterator')
            ->willReturn(new \ArrayIterator([]));

        $this->contentService
            ->expects(self::once())
            ->method('loadRelationList')
            ->with($this->versionInfo)
            ->willReturn($relationList);

        $result = iterator_to_array($this->relationListFacade->getRelations($this->versionInfo));

        self::assertEmpty($result);
    }

    public function testGetRelationsIgnoresItemsWithoutRelations(): void
    {
        $relationListItem = $this->createMock(RelationListItemInterface::class);
        $relationListItem
            ->method('hasRelation')
            ->willReturn(false);

        $relationList = $this->createMock(RelationList::class);
        $relationList->method('getIterator')
            ->willReturn(new \ArrayIterator([$relationListItem]));

        $this->contentService
            ->expects(self::once())
            ->method('loadRelationList')
            ->with(self::identicalTo($this->versionInfo))
            ->willReturn($relationList);

        $result = iterator_to_array($this->relationListFacade->getRelations($this->versionInfo));

        self::assertEmpty($result);
    }

    public function testGetRelationsYieldsRelationsWhenPresent(): void
    {
        $relation = $this->createMock(Relation::class);

        $relationListItem = $this->createMock(RelationListItemInterface::class);
        $relationListItem
            ->method('hasRelation')
            ->willReturn(true);
        $relationListItem
            ->method('getRelation')
            ->willReturn($relation);

        $relationList = $this->createMock(RelationList::class);
        $relationList->method('getIterator')
            ->willReturn(new \ArrayIterator([$relationListItem]));

        $this->contentService
            ->expects(self::once())
            ->method('loadRelationList')
            ->with($this->versionInfo)
            ->willReturn($relationList);

        $result = iterator_to_array($this->relationListFacade->getRelations($this->versionInfo));

        self::assertCount(1, $result);
        self::assertSame($relation, $result[0]);
    }
}
