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
use Ibexa\Contracts\Core\Persistence\User\Handler as PersistenceUserHandler;
use Ibexa\Contracts\Core\Persistence\User\RoleAssignment;
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

    public static function providerForUnCachedMethods(): array
    {
        // string $method, array $arguments, array? $tagGeneratingArguments, array? $keyGeneratingArguments, array? $tags, array? $key, ?mixed $returnValue
        return [
            ['loadTrashItem', [6]],
        ];
    }

    public static function providerForCachedLoadMethodsHit(): array
    {
        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        return [
            [self::NO_DATA_METHOD, [], ''],
        ];
    }

    public static function providerForCachedLoadMethodsMiss(): array
    {
        // string $method, array $arguments, string $key, array? $tagGeneratingArguments, array? $tagGeneratingResults, array? $keyGeneratingArguments, array? $keyGeneratingResults, mixed? $data, bool $multi
        return [
            [self::NO_DATA_METHOD, [], ''],
        ];
    }

    public function testRecover()
    {
        $originalLocationId = 6;
        $targetLocationId = 2;
        $contentId = 42;
        $roleId = 1;

        $this->loggerMock->expects(self::once())->method('logCall');

        $innerHandler = $this->setUpRoleAssignmentInvalidation($originalLocationId, $contentId, $roleId);

        $innerHandler
            ->expects(self::once())
            ->method('recover')
            ->with($originalLocationId, $targetLocationId)
            ->willReturn(null);

        $handler = $this->persistenceCacheHandler->{$this->getHandlerMethodName()}();
        $handler->recover($originalLocationId, $targetLocationId);
    }

    public function testTrashSubtree()
    {
        $locationId = 6;
        $contentId = 42;
        $roleId = 1;

        $this->loggerMock->expects($this->once())->method('logCall');

        $innerHandler = $this->setUpRoleAssignmentInvalidation($locationId, $contentId, $roleId);

        $innerHandler
            ->expects(self::once())
            ->method('trashSubtree')
            ->with($locationId)
            ->willReturn(null);

        $handler = $this->persistenceCacheHandler->{$this->getHandlerMethodName()}();
        $handler->trashSubtree($locationId);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function setUpRoleAssignmentInvalidation(int $locationId, int $contentId, int $roleId): object
    {
        $tags = [
            'c-' . $contentId,
            'lp-' . $locationId,
            'rarl-' . $roleId,
        ];

        $handlerMethodName = $this->getHandlerMethodName();

        $this->loggerMock->expects(self::once())->method('logCall');

        $innerHandler = $this->createMock($this->getHandlerClassName());
        $locationHandlerMock = $this->createMock(LocationHandler::class);
        $userHandlerMock = $this->createMock(PersistenceUserHandler::class);

        $locationHandlerMock
            ->method('load')
            ->willReturn(new Location(['id' => $locationId, 'contentId' => $contentId]));

        $userHandlerMock
            ->method('loadRoleAssignmentsByGroupId')
            ->with($contentId)
            ->willReturn([new RoleAssignment(['roleId' => $roleId, 'contentId' => $contentId])]);

        $this->persistenceHandlerMock
            ->method('contentHandler')
            ->willReturn($this->createStub(ContentHandler::class));

        $this->persistenceHandlerMock
            ->method('locationHandler')
            ->willReturn($locationHandlerMock);

        $this->persistenceHandlerMock
            ->method('userHandler')
            ->willReturn($userHandlerMock);

        $this->persistenceHandlerMock
            ->expects(self::once())
            ->method($this->getHandlerMethodName())
            ->willReturn($innerHandler);
        $matcher = self::exactly(3);

        $this->cacheIdentifierGeneratorMock
            ->expects($matcher)
            ->method('generateTag')->willReturnCallback(function (...$parameters) use ($matcher, $roleId, $contentId, $locationId) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame('role_assignment_role_list', $parameters[0]);
                    $this->assertSame([$roleId], $parameters[1]);
                    $this->assertFalse($parameters[2]);

                    return 'rarl-' . $roleId;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame('content', $parameters[0]);
                    $this->assertSame([$contentId], $parameters[1]);
                    $this->assertFalse($parameters[2]);

                    return 'c-' . $contentId;
                }
                if ($matcher->numberOfInvocations() === 3) {
                    $this->assertSame('location_path', $parameters[0]);
                    $this->assertSame([$locationId], $parameters[1]);
                    $this->assertFalse($parameters[2]);

                    return 'lp-' . $locationId;
                }
            });

        $this->cacheMock
            ->expects(self::once())
            ->method('invalidateTags')
            ->with($tags);

        return $innerHandler;
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
        $matcher = self::exactly(3);

        $this->cacheIdentifierGeneratorMock
            ->expects($matcher)
            ->method('generateTag')->willReturnCallback(function (...$parameters) use ($matcher, $relationSourceContentId, $contentId, $trashedId) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame('content', $parameters[0]);
                    $this->assertSame([$relationSourceContentId], $parameters[1]);
                    $this->assertFalse($parameters[2]);

                    return 'c-' . $relationSourceContentId;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame('content', $parameters[0]);
                    $this->assertSame([$contentId], $parameters[1]);
                    $this->assertFalse($parameters[2]);

                    return 'c-' . $contentId;
                }
                if ($matcher->numberOfInvocations() === 3) {
                    $this->assertSame('location_path', $parameters[0]);
                    $this->assertSame([$trashedId], $parameters[1]);
                    $this->assertFalse($parameters[2]);

                    return 'lp-' . $trashedId;
                }
            });

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
        $matcher = self::exactly(6);

        //one set of arguments and tags for each relation
        $this->cacheIdentifierGeneratorMock
            ->expects($matcher)
            ->method('generateTag')
            ->willReturnCallback(function (...$parameters) use ($matcher, $cacheIdentifierGeneratorArguments, $tags) {
                $this->assertSame(array_merge($cacheIdentifierGeneratorArguments, $cacheIdentifierGeneratorArguments)[$matcher->numberOfInvocations() - 1], $parameters);

                return array_merge($tags, $tags)[$matcher->numberOfInvocations() - 1];
            });

        $this->cacheMock
            ->expects(self::once())
            ->method('invalidateTags')
            ->with($tags);

        /** @var \Ibexa\Contracts\Core\Persistence\Content\Location\Trash\Handler $handler */
        $handler = $this->persistenceCacheHandler->$handlerMethodName();
        $handler->emptyTrash();
    }
}
