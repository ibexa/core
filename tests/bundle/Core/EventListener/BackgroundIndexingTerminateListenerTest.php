<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\EventListener;

use Ibexa\Bundle\Core\EventListener\BackgroundIndexingTerminateListener;
use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Handler as PersistenceHandler;
use Ibexa\Contracts\Core\Search\Handler as SearchHandler;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\Exception;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\HttpKernel\KernelEvents;

class BackgroundIndexingTerminateListenerTest extends TestCase
{
    protected BackgroundIndexingTerminateListener $listener;

    protected PersistenceHandler & MockObject $persistenceMock;

    protected SearchHandler & MockObject $searchMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->persistenceMock = $this->createMock(PersistenceHandler::class);
        $this->searchMock = $this->createMock(SearchHandler::class);
        $this->listener = new BackgroundIndexingTerminateListener(
            $this->persistenceMock,
            $this->searchMock
        );
    }

    protected function tearDown(): void
    {
        unset($this->persistenceMock, $this->searchMock, $this->listener);
        parent::tearDown();
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [
                KernelEvents::TERMINATE => 'reindex',
                KernelEvents::EXCEPTION => 'reindex',
                ConsoleEvents::TERMINATE => 'reindex',
            ],
            BackgroundIndexingTerminateListener::getSubscribedEvents()
        );
    }

    /**
     * @phpstan-return iterable<
     *     string,
     *     array{
     *          0: null|array<\Ibexa\Contracts\Core\Persistence\Content\ContentInfo|\Ibexa\Contracts\Core\Persistence\Content\Location>,
     *          1?: bool
     *     }
     * >
     */
    public static function indexingProvider(): iterable
    {
        $info = new ContentInfo(['id' => 33]);
        $location = new Location(['id' => 44, 'contentId' => 33]);

        yield 'location' => [[$location]];
        yield 'location with logger' => [[$location], true];
        yield 'content info' => [[$info]];
        yield 'content info with logger' => [[$info], true];
        yield 'no values' => [null];
        yield 'no values with logger' => [null, true];
        yield 'location and content info' => [[$location, $info]];
        yield 'location and content info with logger' => [[$info, $location], true];
    }

    /**
     * @dataProvider indexingProvider
     *
     * @param array<\Ibexa\Contracts\Core\Persistence\Content\ContentInfo|\Ibexa\Contracts\Core\Persistence\Content\Location>|null $values
     */
    public function testIndexing(?array $values = null, bool $mockLogger = false): void
    {
        $contentHandlerMock = $this->createMock(Content\Handler::class);
        $this->persistenceMock
            ->expects(self::once())
            ->method('contentHandler')
            ->willReturn($contentHandlerMock);

        if ($values) {
            $contentHandlerMock
                ->expects(self::once())
                ->method('loadContentInfo')
                ->with(33)
                ->willReturn(new ContentInfo(['id' => 33, 'currentVersionNo' => 2, 'status' => ContentInfo::STATUS_PUBLISHED]));

            $contentHandlerMock
                ->expects(self::once())
                ->method('load')
                ->with(33, 2)
                ->willReturn(new Content());

            $this->searchMock
                ->expects(self::once())
                ->method('indexContent')
                ->with(self::isInstanceOf(Content::class));

            $this->searchMock->expects(self::never())->method('indexLocation');
            $this->searchMock->expects(self::never())->method('deleteContent');
            $this->searchMock->expects(self::never())->method('deleteLocation');
        } else {
            $contentHandlerMock->expects(self::never())->method(self::anything());
            $this->searchMock->expects(self::never())->method(self::anything());
        }

        foreach ((array) $values as $value) {
            if ($value instanceof Location) {
                $this->listener->registerLocation($value);
            } elseif ($value instanceof ContentInfo) {
                $this->listener->registerContent($value);
            }
        }

        if ($mockLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->listener->setLogger($logger);

            if ($values) {
                $logger->expects(self::once())
                    ->method('warning')
                    ->with(self::isType('string'));
            } else {
                $logger->expects(self::never())
                    ->method('warning');
            }
        }

        $this->listener->reindex();
    }

    public function indexDeleteProvider(): array
    {
        $location = new Location(['id' => 44, 'contentId' => 33]);
        $info = new ContentInfo(['id' => 33, 'currentVersionNo' => 2, 'status' => ContentInfo::STATUS_PUBLISHED]);

        $infoReturn = self::returnValue($info);
        $infoReturnUnPublished = self::returnValue(new ContentInfo(['id' => 33, 'currentVersionNo' => 2]));
        $returnThrow = self::throwException(new NotFoundException('content', '33'));

        return [
            [$location, $infoReturn, $returnThrow],
            [$location, $returnThrow],
            [$location, $infoReturnUnPublished],

            [$info, $infoReturn, $returnThrow],
            [$info, $returnThrow],
            [$info, $infoReturnUnPublished],
        ];
    }

    /**
     * @dataProvider indexDeleteProvider
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\ContentInfo|\Ibexa\Contracts\Core\Persistence\Content\Location $value
     * @param \PHPUnit\Framework\MockObject\Stub $infoReturn
     * @param \PHPUnit\Framework\MockObject\Stub|null $contentReturn
     */
    public function testIndexDelete(Location|ContentInfo $value, ReturnStub|Exception $infoReturn, Exception $contentReturn = null): void
    {
        $contentHandlerMock = $this->createMock(Content\Handler::class);
        $this->persistenceMock
            ->expects(self::once())
            ->method('contentHandler')
            ->willReturn($contentHandlerMock);

        $contentHandlerMock
            ->expects(self::once())
            ->method('loadContentInfo')
            ->with(33)
            ->will($infoReturn);

        if ($contentReturn) {
            $contentHandlerMock
                ->expects(self::once())
                ->method('load')
                ->with(33, 2)
                ->will($contentReturn);
        } else {
            $contentHandlerMock
                ->expects(self::never())
                ->method('load');
        }

        $this->searchMock->expects(self::never())->method('indexContent');
        $this->searchMock->expects(self::never())->method('indexLocation');

        if ($value instanceof Location) {
            $contentId = $value->contentId;
            $locationId = $value->id;
            $this->listener->registerLocation($value);
        } else {
            $contentId = $value->id;
            $locationId = $value->mainLocationId;
            $this->listener->registerContent($value);
        }

        $this->searchMock
            ->expects(self::once())
            ->method('deleteContent')
            ->with($contentId);

        if ($locationId) {
            $this->searchMock
                ->expects(self::once())
                ->method('deleteLocation')
                ->with($locationId);
        } else {
            $this->searchMock->expects(self::never())->method('deleteLocation');
        }

        $this->listener->reindex();
    }
}
