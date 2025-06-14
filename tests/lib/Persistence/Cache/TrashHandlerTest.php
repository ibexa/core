<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Location\Trash\Handler as TrashHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location\Trashed;
use Ibexa\Contracts\Core\Persistence\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\Content\Trash\TrashItemDeleteResult;
use Ibexa\Core\Persistence\Cache\ContentHandler;
use Ibexa\Core\Persistence\Cache\LocationHandler;

/**
 * Test case for Persistence\Cache\SectionHandler.
 */
class TrashHandlerTest extends AbstractCacheHandlerTestCase
{
    public function getHandlerMethodName(): string
    {
        return 'trashHandler';
    }

    public function getHandlerClassName(): string
    {
        return TrashHandler::class;
    }

    public function providerForUnCachedMethods(): array
    {
        // string $method, array $arguments, array? $tagGeneratingArguments, array? $keyGeneratingArguments, array? $tags, array? $key, ?mixed $returnValue
        return [
            ['loadTrashItem', [6]],
        ];
    }

    public function providerForCachedLoadMethodsHit(): array
    {
        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        return [
        ];
    }

    public function providerForCachedLoadMethodsMiss(): array
    {
        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        return [
        ];
    }

    public function testRecover()
    {
        $originalLocationId = 6;
        $targetLocationId = 2;
        $contentId = 42;

        $tags = [
            'c-' . $contentId,
            'lp-' . $originalLocationId,
        ];

        $handlerMethodName = $this->getHandlerMethodName();

        $this->loggerMock->expects(self::once())->method('logCall');

        $innerHandler = $this->createMock($this->getHandlerClassName());
        $contentHandlerMock = $this->createMock(ContentHandler::class);
        $locationHandlerMock = $this->createMock(LocationHandler::class);

        $locationHandlerMock
            ->method('load')
            ->willReturn(new Location(['id' => $originalLocationId, 'contentId' => $contentId]));

        $this->persistenceHandlerMock
            ->method('contentHandler')
            ->willReturn($contentHandlerMock);

        $this->persistenceHandlerMock
            ->method('locationHandler')
            ->willReturn($locationHandlerMock);

        $this->persistenceHandlerMock
            ->expects(self::once())
            ->method($handlerMethodName)
            ->willReturn($innerHandler);

        $innerHandler
            ->expects(self::once())
            ->method('recover')
            ->with($originalLocationId, $targetLocationId)
            ->willReturn(null);

        $this->cacheIdentifierGeneratorMock
            ->expects(self::exactly(2))
            ->method('generateTag')
            ->withConsecutive(
                ['content', [$contentId], false],
                ['location_path', [$originalLocationId], false]
            )
            ->willReturnOnConsecutiveCalls(
                'c-' . $contentId,
                'lp-' . $originalLocationId
            );

        $this->cacheMock
            ->expects(self::once())
            ->method('invalidateTags')
            ->with($tags);

        $handler = $this->persistenceCacheHandler->$handlerMethodName();
        $handler->recover($originalLocationId, $targetLocationId);
    }

    public function testTrashSubtree()
    {
        $locationId = 6;
        $contentId = 42;

        $tags = [
            'c-' . $contentId,
            'lp-' . $locationId,
        ];

        $handlerMethodName = $this->getHandlerMethodName();

        $this->loggerMock->expects(self::once())->method('logCall');

        $innerHandler = $this->createMock($this->getHandlerClassName());
        $contentHandlerMock = $this->createMock(ContentHandler::class);
        $locationHandlerMock = $this->createMock(LocationHandler::class);

        $locationHandlerMock
            ->method('load')
            ->willReturn(new Location(['id' => $locationId, 'contentId' => $contentId]));

        $this->persistenceHandlerMock
            ->method('contentHandler')
            ->willReturn($contentHandlerMock);

        $this->persistenceHandlerMock
            ->method('locationHandler')
            ->willReturn($locationHandlerMock);

        $this->persistenceHandlerMock
            ->expects(self::once())
            ->method($handlerMethodName)
            ->willReturn($innerHandler);

        $innerHandler
            ->expects(self::once())
            ->method('trashSubtree')
            ->with($locationId)
            ->willReturn(null);

        $this->cacheIdentifierGeneratorMock
            ->expects(self::exactly(2))
            ->method('generateTag')
            ->withConsecutive(
                ['content', [$contentId], false],
                ['location_path', [$locationId], false]
            )
            ->willReturnOnConsecutiveCalls(...$tags);

        $this->cacheMock
            ->expects(self::once())
            ->method('invalidateTags')
            ->with($tags);

        $handler = $this->persistenceCacheHandler->$handlerMethodName();
        $handler->trashSubtree($locationId);
    }

    public function testDeleteTrashItem()
    {
        $trashedId = 6;
        $contentId = 42;
        $relationSourceContentId = 44;

        $handlerMethodName = $this->getHandlerMethodName();

        $innerHandler = $this->createMock($this->getHandlerClassName());

        $trashed = new Trashed(['id' => $trashedId, 'contentId' => $contentId]);
        $innerHandler
            ->expects(self::once())
            ->method('deleteTrashItem')
            ->with($trashedId)
            ->willReturn(new TrashItemDeleteResult(['trashItemId' => $trashedId, 'contentId' => $contentId]));

        $innerHandler
            ->expects(self::once())
            ->method('loadTrashItem')
            ->with($trashedId)
            ->willReturn($trashed);

        $this->persistenceHandlerMock
            ->method($handlerMethodName)
            ->willReturn($innerHandler);

        $contentHandlerMock = $this->createMock(ContentHandler::class);

        $contentHandlerMock
            ->expects(self::once())
            ->method('loadReverseRelations')
            ->with($contentId)
            ->willReturn([new Relation(['sourceContentId' => $relationSourceContentId])]);

        $this->persistenceHandlerMock
            ->method('contentHandler')
            ->willReturn($contentHandlerMock);

        $tags = [
            'c-' . $contentId,
            'lp-' . $trashedId,
            'c-' . $relationSourceContentId,
        ];

        $this->cacheIdentifierGeneratorMock
            ->expects(self::exactly(3))
            ->method('generateTag')
            ->withConsecutive(
                ['content', [$relationSourceContentId], false],
                ['content', [$contentId], false],
                ['location_path', [$trashedId], false]
            )
            ->willReturnOnConsecutiveCalls(
                'c-' . $relationSourceContentId,
                'c-' . $contentId,
                'lp-' . $trashedId
            );

        $this->cacheMock
            ->expects(self::once())
            ->method('invalidateTags')
            ->with($tags);

        /** @var \Ibexa\Contracts\Core\Persistence\Content\Location\Trash\Handler $handler */
        $handler = $this->persistenceCacheHandler->$handlerMethodName();
        $handler->deleteTrashItem($trashedId);
    }

    public function testEmptyTrash()
    {
        $trashedId = 6;
        $contentId = 42;
        $relationSourceContentId = 44;

        $handlerMethodName = $this->getHandlerMethodName();

        $innerHandler = $this->createMock($this->getHandlerClassName());

        $innerHandler
            ->expects(self::exactly(2))
            ->method('findTrashItems')
            ->willReturn(new Location\Trash\TrashResult([
                'items' => [new Trashed(['id' => $trashedId, 'contentId' => $contentId])],
                // trigger the bulk loading several times to have some minimal coverage on the loop exit logic
                'totalCount' => 101,
            ]));

        $this->persistenceHandlerMock
            ->method($handlerMethodName)
            ->willReturn($innerHandler);

        $contentHandlerMock = $this->createMock(ContentHandler::class);

        $contentHandlerMock
            ->expects(self::exactly(2))
            ->method('loadReverseRelations')
            ->with($contentId)
            ->willReturn([new Relation(['sourceContentId' => $relationSourceContentId])]);

        $this->persistenceHandlerMock
            ->method('contentHandler')
            ->willReturn($contentHandlerMock);

        $cacheIdentifierGeneratorArguments = [
            ['content', [$relationSourceContentId], false],
            ['content', [$contentId], false],
            ['location_path', [$trashedId], false],
        ];

        $tags = [
            'c-' . $relationSourceContentId,
            'c-' . $contentId,
            'lp-' . $trashedId,
        ];

        //one set of arguments and tags for each relation
        $this->cacheIdentifierGeneratorMock
            ->expects(self::exactly(6))
            ->method('generateTag')
            ->withConsecutive(...array_merge($cacheIdentifierGeneratorArguments, $cacheIdentifierGeneratorArguments))
            ->willReturnOnConsecutiveCalls(...array_merge($tags, $tags));

        $this->cacheMock
            ->expects(self::once())
            ->method('invalidateTags')
            ->with($tags);

        /** @var \Ibexa\Contracts\Core\Persistence\Content\Location\Trash\Handler $handler */
        $handler = $this->persistenceCacheHandler->$handlerMethodName();
        $handler->emptyTrash();
    }
}
